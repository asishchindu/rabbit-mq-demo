# RabbitMQ PHP Demo

A minimal producer/consumer example using [RabbitMQ](https://www.rabbitmq.com/) and [php-amqplib](https://github.com/php-amqplib/php-amqplib), packaged with Docker Compose.

- **Producer** — a small web page where you type a message; it is published to the `tasks` queue.
- **Consumer** — a background worker that takes messages off the queue, simulates 1 second of work, upper-cases the text and records the result.
- **RabbitMQ** — the message broker, with the management UI enabled.

```
 Browser ──POST──▶ producer (PHP web) ──publish──▶ [ RabbitMQ: tasks queue ] ──deliver──▶ consumer (PHP worker)
                         ▲                                                                       │
                         └──────────────── reads processed.log (shared volume) ◀────── writes ───┘
```

## Requirements

- Docker with Docker Compose v2

## Getting started

```bash
docker compose up -d --build
```

Then open:

| Service | URL | Notes |
|---|---|---|
| Web app (producer) | http://localhost:8080 | Send messages and see processed results (refresh the page) |
| RabbitMQ management UI | http://localhost:15672 | Login `guest` / `guest` |

Watch the worker process messages:

```bash
docker compose logs -f consumer
```

Stop everything:

```bash
docker compose down        # keep data
docker compose down -v     # also remove the shared volume
```

## Project structure

```
.
├── docker-compose.yml   # rabbitmq, producer and consumer services
├── Dockerfile           # PHP 8.3 CLI + sockets extension + Composer dependencies
├── composer.json        # php-amqplib dependency, App\ autoloading
├── src/
│   └── Rabbit.php       # shared connection (with retry) and queue declaration
├── public/
│   └── index.php        # producer: web form that publishes messages
└── consumer.php         # consumer: long-running worker
```

## How it works

- **Durable queue and persistent messages** — the `tasks` queue is declared durable and messages are published with `delivery_mode = persistent`, so queued messages survive a broker restart.
- **Manual acknowledgements** — the consumer acks a message only after it has finished processing it. If a worker crashes mid-task, RabbitMQ redelivers the message.
- **Fair dispatch** — `basic_qos(prefetch_count = 1)` gives each worker one message at a time, so work is shared evenly across workers.
- **Startup ordering** — the PHP services wait for RabbitMQ's healthcheck to pass, and `Rabbit::connect()` retries the connection as a safety net.

## Scaling the consumer

Run several workers and watch RabbitMQ distribute messages between them:

```bash
docker compose up -d --scale consumer=3
docker compose logs -f consumer
```

## Configuration

Connection settings are read from environment variables (set in `docker-compose.yml`):

| Variable | Default |
|---|---|
| `RABBITMQ_HOST` | `localhost` |
| `RABBITMQ_PORT` | `5672` |
| `RABBITMQ_USER` | `guest` |
| `RABBITMQ_PASS` | `guest` |

## Publishing from the command line

```bash
curl -X POST -d 'message=hello rabbit' http://localhost:8080/
```
