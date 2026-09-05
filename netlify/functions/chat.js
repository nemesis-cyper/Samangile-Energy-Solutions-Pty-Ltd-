/**
 * Netlify Serverless Function for Chat Handler
 * Replaces Chat.php with a serverless backend
 */

// Mock AI response for demonstration
// In production, integrate with OpenAI, Hugging Face, or your preferred AI service
async function getAIResponse(message, sessionId) {
    // Example responses based on keywords
    const lowerMessage = message.toLowerCase();
    
    if (lowerMessage.includes('solar') || lowerMessage.includes('renewable')) {
        return "We specialize in solar energy solutions and renewable energy installations. Would you like more information about our solar panel systems or maintenance services?";
    }
    if (lowerMessage.includes('quote') || lowerMessage.includes('price')) {
        return "I can help you get a quote! Please use our quote request form to provide details about your project, and our team will contact you with a custom quote.";
    }
    if (lowerMessage.includes('fabrication') || lowerMessage.includes('welding')) {
        return "Our fabrication team specializes in structural steel work, welding, and custom metal fabrication. Can you tell me more about your project requirements?";
    }
    if (lowerMessage.includes('contact') || lowerMessage.includes('phone')) {
        return "You can contact us via the contact form on our website, or call our office directly. Our team typically responds within 24 hours.";
    }
    
    // Default response
    return "Thank you for your inquiry! We're here to help with your energy and fabrication needs. Could you provide more details about what you're looking for?";
}

exports.handler = async (event) => {
    // Only allow POST requests
    if (event.httpMethod !== 'POST') {
        return {
            statusCode: 405,
            headers: {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: false,
                message: 'Method Not Allowed'
            })
        };
    }

    // Handle CORS preflight
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers': 'Content-Type'
            }
        };
    }

    try {
        const data = JSON.parse(event.body || '{}');
        const { message, sessionId } = data;

        if (!message || typeof message !== 'string') {
            return {
                statusCode: 400,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    success: false,
                    message: 'Invalid message provided'
                })
            };
        }

        // Get AI response (implement your AI logic here)
        const reply = await getAIResponse(message, sessionId);

        // Log the interaction (useful for analytics and debugging)
        console.log('Chat interaction:', {
            sessionId,
            userMessage: message,
            timestamp: new Date().toISOString(),
            reply
        });

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                reply: reply,
                timestamp: new Date().toISOString()
            })
        };

    } catch (error) {
        console.error('Chat function error:', error);
        return {
            statusCode: 500,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                success: false,
                message: 'Server error processing chat request'
            })
        };
    }
};
