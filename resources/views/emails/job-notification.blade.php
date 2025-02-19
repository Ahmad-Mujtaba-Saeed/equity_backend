<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Job Opportunity on Equity Circle</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1e88e5;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-width: 200px;
            height: auto;
            display: inline-block !important;
        }
        .content {
            padding: 20px;
            color: #333;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1e88e5;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            font-weight: bold;
            color: #1e88e5;
        }
        .job-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        img {
            max-width: 100%;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ $frontendUrl }}" style="text-decoration: none;">
                <img src="{{ $logoUrl }}" alt="Equity Circle Logo" style="display: inline-block !important;">
            </a>
        </div>
        
        <div class="content">
            <h2>New Job Opportunity!</h2>
            <p>Hello {{ $data['recipient_name'] }},</p>
            <p>A new job opportunity has been posted on Equity Circle:</p>
            
            <div class="job-details">
                <h3 class="highlight">{{ $data['job_title'] }}</h3>
                <p><strong>Company:</strong> {{ $data['company_name'] }}</p>
                <p><strong>Description:</strong> {{ $data['job_description'] }}</p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $frontendUrl }}/job-list-detail/{{ $data['job_id'] }}" class="button" style="color: white !important; text-decoration: none;">
                    View Job Details
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Find your next career opportunity on Equity Circle - Your platform for professional growth.</p>
            <p>Connect with companies, explore opportunities, and advance your career.</p>
            <p><a href="{{ $frontendUrl }}" style="color: #1e88e5; text-decoration: none;">Visit Equity Circle</a></p>
        </div>
    </div>
</body>
</html>
