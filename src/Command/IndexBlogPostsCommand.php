<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\PostRepository;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Document\VectorizerInterface;
use Symfony\AI\Store\StoreInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:index-blog-posts',
    description: 'Index blog posts into the pgvector store for similarity search',
)]
final class IndexBlogPostsCommand extends Command
{
    public function __construct(
        private readonly PostRepository $postRepository,
        #[Autowire(service: 'ai.store.postgres.default')]
        private readonly StoreInterface $store,
        #[Autowire(service: 'ai.vectorizer.openai')]
        private readonly VectorizerInterface $vectorizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $posts = $this->postRepository->findAll();

        $io->info(\sprintf('Indexing %d blog posts...', \count($posts)));

        foreach ($posts as $post) {
            $text = \sprintf(
                "Title: %s\n\nSummary: %s\n\n%s",
                $post->getTitle(),
                $post->getSummary(),
                $post->getContent(),
            );

            $document = new TextDocument(
                Uuid::v5(Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'), (string) $post->getId()),
                $text,
                new Metadata([
                    'title' => $post->getTitle(),
                    'slug' => $post->getSlug(),
                ]),
            );

            $vectorDocument = $this->vectorizer->vectorize($document);
            $this->store->add($vectorDocument);

            $io->writeln(\sprintf('  Indexed: %s', $post->getTitle()));
        }

        $io->success('All blog posts have been indexed.');

        return Command::SUCCESS;
    }
}
