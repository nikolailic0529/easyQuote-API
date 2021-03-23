* [Paginate Teams](#paginate-teams)
* [Show Teams List](#show-teams-list)
* [Create Team](#create-team)
* [Update Team](#update-team)
* [Delete Team](#delete-team)
* [Show Team](#show-team)

# Paginate Teams

    [GET] api/teams
    
    order_by_created_at
    order_by_team_name
    order_by_monthly_goal_amount

# Show Teams List

    [GET] api/teams/list

    Response:
    
    [
        "*": {
            "id",
            "team_name"
        }
    ]

# Show Team

    [GET] api/teams/{team_uuid}

# Create Team

    [POST] api/teams
        
    Payload:
    
    {
        "team_name": {string_max_191_chars}, *
        "monthly_goal_amount": {numeric_value} (min: 0, max: 999_999_999)
    }

# Update Team

    [POST] api/teams/{team_uuid}
        
    Payload:
    
    {
        "team_name": {string_max_191_chars}, *
        "monthly_goal_amount": {numeric_value} (min: 0, max: 999_999_999)
    }

# Delete Team

    [DELETE] api/teams/{team_uuid}
