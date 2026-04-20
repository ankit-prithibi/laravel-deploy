## Docker setup

### Start
docker compose -f docker-compose.prod.yml up -d

### Stop
docker compose -f docker-compose.prod.yml down

### Rebuild
docker compose -f docker-compose.prod.yml up --build -d

### Logs
docker compose -f docker-compose.prod.yml logs -f

### Enter app container
docker compose -f docker-compose.prod.yml exec app bash

copy .env.example to .env
set DB_HOST=host.docker.internal for Mac local MySQL