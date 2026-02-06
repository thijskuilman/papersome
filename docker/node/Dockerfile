FROM node:20-alpine

WORKDIR /app

ENV PUPPETEER_SKIP_DOWNLOAD=true

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build
