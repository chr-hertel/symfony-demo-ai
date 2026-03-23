Symfony Demo + AI
=================

This project extends the [Symfony Demo Application][1] with a RAG-powered travel chatbot
using [Symfony AI][2], PostgreSQL with [pgvector][3], and OpenAI.

The blog is pre-filled with 30 travel articles about European destinations. The chat feature
uses similarity search over the embedded blog posts to answer user questions and links to
source articles.

Requirements
------------

  * PHP 8.4 or higher;
  * PDO-PostgreSQL PHP extension enabled;
  * Docker (for PostgreSQL with pgvector);
  * An [OpenAI API key][4];
  * and the [usual Symfony application requirements][5].

Installation
------------

```bash
git clone https://github.com/chr-hertel/symfony-demo-ai.git
cd symfony-demo-ai
composer install
```

Set your OpenAI API key in `.env.local`:

```bash
echo "OPENAI_API_KEY=sk-your-key-here" >> .env.local
```

Setup
-----

Start the database and initialize everything:

```bash
# Start PostgreSQL with pgvector
docker compose up -d

# Create the database schema
php bin/console doctrine:schema:create

# Load the travel blog fixtures
php bin/console doctrine:fixtures:load --no-interaction

# Create the vector store table
php bin/console ai:store:setup ai.store.postgres.default

# Index blog posts for similarity search
php bin/console ai:store:index blog_posts
```

Usage
-----

[Download Symfony CLI][6] and run:

```bash
symfony serve
```

Then open <https://localhost:8000> and click **Chat** in the navigation bar.

Tests
-----

```bash
./bin/phpunit
```

[1]: https://github.com/symfony/demo
[2]: https://github.com/symfony/ai
[3]: https://github.com/pgvector/pgvector
[4]: https://platform.openai.com/api-keys
[5]: https://symfony.com/doc/current/setup.html#technical-requirements
[6]: https://symfony.com/download
