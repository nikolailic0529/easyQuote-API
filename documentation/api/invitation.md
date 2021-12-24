* [Invitation Flow](#invitation-flow)
  ** [Show the list of invitations](#show-the-list-of-invitations)
  ** [Invite a new user](#invite-a-new-user)
  ** [Resend the invitation](#resend-the-invitation)
  ** [Cancel the invitation](#cancel-the-invitation)
* [User Registration](#user-registration)
  ** [Validate the invitation](#validate-the-invitation)
  ** [Register by an invitation](#register-by-the-invitation)


# Invitation Flow

## Show the list of invitations

    [GET] api/invitaitons

    Response:
    {
        "data": [
            "*": {
                "id",
                "user_id",
                "role_id",
                "email",
                "role_name",
                "invitation_token",
                "host",
                "created_at",
                "expires_at",
                "is_expired",
            }
        ],
        "current_page",
        "first_page_url",
        "from",
        "last_page",
        "last_page_url",
        "links":[
            "*": {
                "url",
                "label",
                "active",
            },
        ],
        "next_page_url",
        "path",
        "per_page",
        "prev_page_url",
        "to",
        "total",
    }

## Invite a new user

    [POST] api/users

    Payload:
    {
        "role_id": {uuid}, *
        "host": {string}, *
        "email": {string}, *
    }

## Resend the invitation

    [PUT] api/invitations/resend/{invitation_token}

## Cancel the invitation

    [PUT] api/invitations/cancel/{invitation_token}

## Delete the invitation

    [DELETE] api/invitations/{invitation_token}


# User Registration

## Validate the invitation
    
    [GET] api/auth/signup/{invitation_token}

    Response:
    {
        "email",
        "role_name"
    }

    The corresponding exception will be raised when the given token is expired or the invation was cancelled.

## Register by the invitation

    [POST] api/auth/signup/{invitation_token}

    Payload:
    {
        "local_ip": {string},
        "password": {string}, *
        "password_confirmation": {string}, *
        "g_recaptcha": {string},
    }

    Response:
    {
        "id",
        "first_name",
        "last_name",
        "email",
        "created_at",
        "activated_at",
    }
    