window.addEventListener('load', () => {
  window.ui = SwaggerUIBundle({
    url: '../openapi.yaml',
    dom_id: '#swagger-ui',
    deepLinking: true,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIBundle.SwaggerUIStandalonePreset
    ],
    layout: 'BaseLayout'
  });
});
