<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} — Swagger UI</title>

        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.34.0/swagger-ui.css">
    </head>
    <body>
        <div id="swagger-ui" data-specification-url="{{ route('doc.api.specification', absolute: false) }}"></div>

        <noscript>Enable JavaScript to view the API documentation.</noscript>

        <script src="https://unpkg.com/swagger-ui-dist@5.34.0/swagger-ui-bundle.js" crossorigin="anonymous"></script>
        <script>
            SwaggerUIBundle({
                url: document.getElementById('swagger-ui').dataset.specificationUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                validatorUrl: null,
            });
        </script>
    </body>
</html>
