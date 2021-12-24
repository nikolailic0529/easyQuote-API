* [Create Company Note](#create-company-note)
* [Update Company Note](#update-company-note)
* [Delete Company Note](#delete-company-note)

# Create Company Note

    [POST] api/companies/{company_uuid}/company-notes

    Payload:
    {
        "text": {string_max_20000_chars} *
    }

# Update Company Note

    [PATCH] api/companies/company-notes/{note_uuid}

    Payload:
    {
        "text": {string_max_20000_chars} *
    }

# Delete Company Note

    [DELETE] api/companies/company-notes/{note_uuid}