FROM node:20-slim

WORKDIR /app

COPY package.json ./
RUN npm install --production

COPY . .

EXPOSE ${PORT:-3001}

CMD ["node", "whatsapp.js"]
