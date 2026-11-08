<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>TicoGames API</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css"></head>
<body><div id="swagger-ui"></div><script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script><script>
SwaggerUIBundle({ url: '/api/openapi.yaml', dom_id: '#swagger-ui', withCredentials: true,
  requestInterceptor: request => {
    request.headers.Accept = 'application/json';
    const token = document.cookie.split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='));
    if (token) request.headers['X-XSRF-TOKEN'] = decodeURIComponent(token.slice('XSRF-TOKEN='.length));
    return request;
  }
});
</script></body></html>
