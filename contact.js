// netlify/functions/contact.js
//
// Handles POST requests from the website contact form.
// Validates input, then sends an email via Resend.
//
// Required environment variable (set in Netlify dashboard, NOT in code):
//   RESEND_API_KEY   - your Resend API key (starts with "re_")
//
// Optional environment variables:
//   CONTACT_FROM_EMAIL - verified sender, e.g. "Samangile Website <contact@samangileenergysol.co.za>"
//                         Defaults to Resend's test sender if not set (only works for testing).
//   CONTACT_TO_EMAIL   - where submissions should be delivered, e.g. "info@samangileenergysol.co.za"

const { Resend } = require("resend");

const resend = new Resend(process.env.RESEND_API_KEY);

const FROM_EMAIL = process.env.CONTACT_FROM_EMAIL || "Samangile Website <onboarding@resend.dev>";
const TO_EMAIL = process.env.CONTACT_TO_EMAIL || "info@samangileenergysol.co.za";

// Basic email format check (not exhaustive, just catches obvious mistakes)
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

exports.handler = async function (event) {
  // CORS headers so the form can be submitted from the live site
  const headers = {
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Headers": "Content-Type",
    "Access-Control-Allow-Methods": "POST, OPTIONS",
  };

  // Browsers send a preflight OPTIONS request before POST — just acknowledge it
  if (event.httpMethod === "OPTIONS") {
    return { statusCode: 204, headers, body: "" };
  }

  if (event.httpMethod !== "POST") {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: "Method not allowed" }),
    };
  }

  let data;
  try {
    data = JSON.parse(event.body);
  } catch (err) {
    return {
      statusCode: 400,
      headers,
      body: JSON.stringify({ error: "Invalid request body" }),
    };
  }

  const name = (data.name || "").trim();
  const email = (data.email || "").trim();
  const phone = (data.phone || "").trim();
  const message = (data.message || "").trim();

  // Honeypot field — if a bot fills this hidden field, silently pretend success
  if (data["bot-field"]) {
    return { statusCode: 200, headers, body: JSON.stringify({ success: true }) };
  }

  // Validation
  const errors = [];
  if (!name) errors.push("Name is required");
  if (!email) errors.push("Email is required");
  else if (!EMAIL_REGEX.test(email)) errors.push("Email address is not valid");
  if (!message) errors.push("Message is required");

  if (errors.length > 0) {
    return {
      statusCode: 400,
      headers,
      body: JSON.stringify({ error: errors.join(", ") }),
    };
  }

  // Build the email body
  const htmlBody = `
    <h2>New contact form submission</h2>
    <p><strong>Name:</strong> ${escapeHtml(name)}</p>
    <p><strong>Email:</strong> ${escapeHtml(email)}</p>
    ${phone ? `<p><strong>Phone:</strong> ${escapeHtml(phone)}</p>` : ""}
    <p><strong>Message:</strong></p>
    <p>${escapeHtml(message).replace(/\n/g, "<br>")}</p>
    <hr>
    <p style="color:#888;font-size:12px;">Sent from the Samangile Energy Solutions website contact form.</p>
  `;

  try {
    const { error } = await resend.emails.send({
      from: FROM_EMAIL,
      to: [TO_EMAIL],
      replyTo: email,
      subject: `New website enquiry from ${name}`,
      html: htmlBody,
    });

    if (error) {
      console.error("Resend error:", error);
      return {
        statusCode: 502,
        headers,
        body: JSON.stringify({ error: "Failed to send email. Please try again later." }),
      };
    }

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({ success: true }),
    };
  } catch (err) {
    console.error("Unexpected error:", err);
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ error: "Something went wrong. Please try again later." }),
    };
  }
};

// Prevent HTML injection from form fields into the email body
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
