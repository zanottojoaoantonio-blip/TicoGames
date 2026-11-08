FROM nginx:1.28-alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY *.html /usr/share/nginx/html/
COPY assets/ /usr/share/nginx/html/assets/
COPY env-config.template.js /etc/nginx/templates/config.js.template
ENV API_BASE_URL=""
ENV NGINX_ENVSUBST_OUTPUT_DIR=/usr/share/nginx/html/assets/js

EXPOSE 80
