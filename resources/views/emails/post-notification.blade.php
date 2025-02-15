<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['type'] === 'new_post' ? 'New Post' : ($data['type'] === 'like' ? 'New Like' : 'New Comment') }} on Equity Circle</title>
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
            @if($data['type'] === 'new_post')
                <h2>New Post on Equity Circle!</h2>
                <p>Hello {{ $data['recipient_name'] }},</p>
                <p>A new post has been shared on Equity Circle:</p>
                <p class="highlight">{!! ($data['post_title']) !!}</p>
                
            @elseif($data['type'] === 'like')
                <h2>Your Post Received a Like!</h2>
                <p>Hello {{ $data['recipient_name'] }},</p>
                <p><span class="highlight">{{ $data['actor_name'] }}</span> liked your post:</p>
                <p class="highlight">"{{ $data['post_title'] }}"</p>

            @elseif($data['type'] === 'comment')
                <h2>New Comment on Your Post!</h2>
                <p>Hello {{ $data['recipient_name'] }},</p>
                <p><span class="highlight">{{ $data['actor_name'] }}</span> commented on your post:</p>
                <p class="highlight">"{{ $data['post_title'] }}"</p>

            @elseif($data['type'] === 'reply')
                <h2>New Reply to Your Comment!</h2>
                <p>Hello {{ $data['recipient_name'] }},</p>
                <p><span class="highlight">{{ $data['actor_name'] }}</span> replied to your comment on the post:</p>
                <p class="highlight">"{{ $data['post_title'] }}"</p>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $frontendUrl }}/post/{{ $data['post_id'] }}" class="button" style="color: white !important; text-decoration: none;">
                    View on Equity Circle
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Join the conversation on Equity Circle - Your platform for meaningful connections and professional growth.</p>
            <p>Connect with professionals, share insights, and stay updated with the latest in your field.</p>
            <p><a href="{{ $frontendUrl }}" style="color: #1e88e5; text-decoration: none;">Visit Equity Circle</a></p>
        </div>
    </div>
</body>
</html>
