FROM python:3.12-alpine AS build
WORKDIR /app
COPY . .
ARG SITE_URL=https://charlot-demo.5.189.135.4.sslip.io
RUN python3 build.py --site-url "$SITE_URL" \
    && python3 tests/check_seo.py \
    && python3 deployment/generate_redirects.py

FROM nginx:stable-alpine
COPY deployment/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/deployment/redirects.conf /etc/nginx/charlot-redirects.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8080/en/ || exit 1
