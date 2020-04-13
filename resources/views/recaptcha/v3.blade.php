<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Recaptcha V3</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
    <script src="{{ 'https://www.google.com/recaptcha/api.js?render='.config('services.recaptcha_v3.key') }}"></script>
</head>

<body>

    <div>
        <div class="container pt-4">
            <label for="recaptcha_v2">Recaptcha V3 token</label>
            <div id="g-recaptcha" class="form-group"></div>
            <div class="form-group"><textarea id="recaptcha_v3" class="form-control" rows="4" cols="100"></textarea></div>
        </div>
    </div>

    <script>
        (function () {
            grecaptcha.ready(function () {
                grecaptcha.execute("{{ config('services.recaptcha_v3.key') }}", { action: 'login' }).then(function (token_v3) {
                    console.log({token_v3});
                    document.querySelector('#recaptcha_v3').value = token_v3;
                })
            });
        })()
    </script>
</body>

</html>