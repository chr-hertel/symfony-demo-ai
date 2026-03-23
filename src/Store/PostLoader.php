<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Store;

use App\Repository\PostRepository;
use Symfony\AI\Store\Document\LoaderInterface;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

final class PostLoader implements LoaderInterface
{
    public function __construct(
        private readonly PostRepository $postRepository,
    ) {
    }

    public function load(?string $source = null, array $options = []): iterable
    {
        foreach ($this->postRepository->findAll() as $post) {
            $text = \sprintf(
                "Title: %s\n\nSummary: %s\n\n%s",
                $post->getTitle(),
                $post->getSummary(),
                $post->getContent(),
            );

            yield new TextDocument(
                Uuid::v5(Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'), (string) $post->getId()),
                $text,
                new Metadata([
                    'title' => $post->getTitle(),
                    'slug' => $post->getSlug(),
                ]),
            );
        }
    }
}
