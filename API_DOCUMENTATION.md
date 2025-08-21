# Support System API Documentation

## Overview

This Laravel application provides a comprehensive support system API with:

1. **Newsletter/Email Capture System** - For collecting and managing email subscriptions
2. **Support Ticket System** - For handling bug reports, feature requests, and contact forms
3. **Multi-Subdomain Support** - Each subdomain can have its own isolated data

## Features

### Newsletter System
- Email subscription with verification
- Subdomain isolation
- Metadata storage for additional subscriber information
- Unsubscribe functionality
- Active/inactive subscriber management

### Support Ticket System
- Multiple ticket types: bug reports, feature requests, contact forms, general support
- Automatic ticket ID generation (TKT-XXXXXXXX format)
- Priority levels: low, medium, high, urgent
- Status tracking: open, in_progress, resolved, closed
- Browser and OS detection
- File attachments support
- Metadata for custom fields

### Multi-Subdomain Support
- Use `X-Subdomain` header or `subdomain` query parameter
- Each subdomain maintains separate data
- CORS enabled for cross-origin requests

## API Endpoints

### Newsletter Endpoints

#### Subscribe to Newsletter
```bash
POST /api/newsletter
Content-Type: application/json
X-Subdomain: app1

{
    "email": "user@example.com",
    "name": "John Doe",
    "source": "homepage",
    "metadata": {
        "preferences": ["product_updates", "blog_posts"]
    }
}
```

#### Verify Email
```bash
GET /api/newsletter/verify/{token}
```

#### Get Subscriptions
```bash
GET /api/newsletter
X-Subdomain: app1
```

#### Unsubscribe
```bash
DELETE /api/newsletter/unsubscribe/{email}
X-Subdomain: app1
```

### Support Ticket Endpoints

#### Create Support Ticket
```bash
POST /api/support
Content-Type: application/json
X-Subdomain: app1

{
    "type": "bug_report",
    "name": "John Doe",
    "email": "user@example.com",
    "subject": "Login page not working",
    "message": "I can't log into my account. The page keeps showing an error.",
    "priority": "high",
    "category": "authentication",
    "browser": "Chrome 118.0",
    "os": "Windows 11",
    "url": "https://app1.example.com/login",
    "metadata": {
        "user_id": 12345,
        "account_type": "premium"
    }
}
```

#### Get All Tickets
```bash
GET /api/support?type=bug_report&status=open&priority=high
X-Subdomain: app1
```

#### Get Specific Ticket
```bash
GET /api/support/{ticket_id}
X-Subdomain: app1
```

#### Update Ticket Status
```bash
PUT /api/support/{ticket_id}
Content-Type: application/json
X-Subdomain: app1

{
    "status": "resolved",
    "priority": "medium"
}
```

#### Get Tickets by Email
```bash
GET /api/support/email/{email}
X-Subdomain: app1
```

#### Get Statistics
```bash
GET /api/support/statistics
X-Subdomain: app1
```

## Response Format

All API responses follow this format:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    }
}
```

Error responses:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        // Validation errors if applicable
    }
}
```

## Ticket Types

- `bug_report` - Bug Report
- `feature_request` - Feature Request
- `contact` - General Contact
- `general_support` - General Support

## Priority Levels

- `low` - Low Priority
- `medium` - Medium Priority (default)
- `high` - High Priority
- `urgent` - Urgent

## Status Values

- `open` - Open (default)
- `in_progress` - In Progress
- `resolved` - Resolved
- `closed` - Closed

## Usage Examples

### Frontend Integration

```javascript
// Subscribe to newsletter
const subscribeToNewsletter = async (email, subdomain) => {
    const response = await fetch('/api/newsletter', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Subdomain': subdomain
        },
        body: JSON.stringify({
            email: email,
            source: 'frontend_form'
        })
    });
    
    return response.json();
};

// Submit support ticket
const submitSupportTicket = async (ticketData, subdomain) => {
    const response = await fetch('/api/support', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Subdomain': subdomain
        },
        body: JSON.stringify(ticketData)
    });
    
    return response.json();
};
```

### cURL Examples

```bash
# Subscribe to newsletter
curl -X POST http://localhost:8000/api/newsletter \
  -H "Content-Type: application/json" \
  -H "X-Subdomain: myapp" \
  -d '{
    "email": "test@example.com",
    "name": "Test User",
    "source": "api_test"
  }'

# Create bug report
curl -X POST http://localhost:8000/api/support \
  -H "Content-Type: application/json" \
  -H "X-Subdomain: myapp" \
  -d '{
    "type": "bug_report",
    "name": "John Doe",
    "email": "john@example.com",
    "subject": "Payment processing error",
    "message": "Getting error when trying to process payment",
    "priority": "high",
    "browser": "Chrome 118",
    "os": "macOS"
  }'

# Get ticket statistics
curl -X GET "http://localhost:8000/api/support/statistics" \
  -H "X-Subdomain: myapp"
```

## Health Check

```bash
GET /api/health
```

Returns API status and version information.

## CORS Support

The API includes CORS headers to allow cross-origin requests from web applications on different domains/subdomains.

## Next Steps

1. **Email Integration**: Set up mail configuration in `.env` and create email templates for:
   - Newsletter verification emails
   - Support ticket confirmations
   - Ticket status updates

2. **File Upload**: Implement file upload functionality for support ticket attachments

3. **Rate Limiting**: Add rate limiting to prevent spam

4. **Authentication**: Add API authentication for admin endpoints

5. **Webhooks**: Add webhook support for real-time integrations

6. **Analytics**: Add analytics and reporting features
