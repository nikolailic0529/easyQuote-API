* [Issue Personal Access Token](#issue-personal-access-token)
* [Logout](#logout)


# Issue Personal Access Token
    
    [POST] api/auth/signin

    Payload:
    {
        "email": {string}, *
        "password": {string}, *
        "g_recaptcha": {string} (token string, if enabled)
    }

    Response:
    {
        "access_token": {string},
        "expires_at": {date_time_string},
        "recaptcha_response": {string} (optional),
        "token_type": {string}
    }

# Logout
    
    # Logout and revoke an active access token
    
    [GET] api/auth/logout
    
    
    