# Stage 1: Build Vue SPA
FROM docker.io/library/node:24-alpine AS builder

WORKDIR /app

# Copy package definitions first for layer caching
COPY package.json package-lock.json ./

# Install exact dependencies
RUN npm ci

# Copy application source code
COPY . .

# Build production bundle
RUN npm run build

# Stage 2: Serve with Nginx
FROM docker.io/library/nginx:1.31.7-alpine

# Ensure log files are accessible in rootless podman
RUN rm -f /var/log/nginx/error.log /var/log/nginx/access.log \
    && touch /var/log/nginx/error.log /var/log/nginx/access.log \
    && chmod -R 777 /var/log/nginx /var/cache/nginx /run

# Copy custom Nginx configuration for Vue SPA routing
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy compiled assets from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
