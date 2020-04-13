<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Recaptcha V2</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
    <script>
        var verifyCallback = (token_v2) => {
            console.log({token_v2});
            document.querySelector('#recaptcha_v2').value = token_v2;
        };
        var onloadCallback = function () {
            grecaptcha.render('g-recaptcha', {
                'sitekey' : '{{ config('services.recaptcha_v2.key') }}',
                'callback' : verifyCallback,
                'theme' : 'light'
            });
        };
    </script>
</head>

<body>
    <div class="container pt-4">
        <label for="recaptcha_v2">Recaptcha V2 token</label>
        <div id="g-recaptcha" class="form-group"></div>
        <div class="form-group"><textarea id="recaptcha_v2" class="form-control" rows="4" cols="100"></textarea></div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
</body>

</html>