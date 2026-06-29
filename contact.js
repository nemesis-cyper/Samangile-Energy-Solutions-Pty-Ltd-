exports.handler = async (event) => {
  if (event.httpMethod !== "POST") {
    return {
      statusCode: 405,
      body: JSON.stringify({
        message: "Method Not Allowed"
      })
    };
  }

  try {
    const data = JSON.parse(event.body);

    const { name, email, phone, company, message } = data;

    console.log("New Contact Request:", {
      name,
      email,
      phone,
      company,
      message
    });

    return {
      statusCode: 200,
      body: JSON.stringify({
        success: true,
        message: "Thank you! Your enquiry has been received."
      })
    };

  } catch (error) {
    return {
      statusCode: 500,
      body: JSON.stringify({
        success: false,
        message: "Server Error"
      })
    };
  }
};