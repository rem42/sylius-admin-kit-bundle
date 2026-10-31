<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use App\Enum\BookCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:load-books', 'Loads the demo data set (authors and books)')]
final class LoadBooksCommand
{
    /** @var list<array{string, string, BookCategory, bool, ?string}> */
    private const BOOKS = [
        ['Dune', 'Frank Herbert', BookCategory::SCIENCE_FICTION, true, 'Sur la planète désertique Arrakis, Paul Atréides découvre son destin.'],
        ['Fondation', 'Isaac Asimov', BookCategory::SCIENCE_FICTION, true, null],
        ['Le Hobbit', 'J. R. R. Tolkien', BookCategory::FANTASY, false, null],
        ['Le Comte de Monte-Cristo', 'Alexandre Dumas', BookCategory::FICTION, true, null],
        ['Le Nom de la rose', 'Umberto Eco', BookCategory::MYSTERY, true, null],
        ['Sapiens', 'Yuval Noah Harari', BookCategory::HISTORY, false, null],
        ['Clean Code', 'Robert C. Martin', BookCategory::TECHNOLOGY, true, null],
        ['Neuromancien', 'William Gibson', BookCategory::SCIENCE_FICTION, true, null],
        ['Les Misérables', 'Victor Hugo', BookCategory::FICTION, true, null],
        ['The Pragmatic Programmer', 'Andrew Hunt', BookCategory::TECHNOLOGY, false, null],
        ['Hypérion', 'Dan Simmons', BookCategory::SCIENCE_FICTION, true, null],
        ['Le Crime de l\'Orient-Express', 'Agatha Christie', BookCategory::MYSTERY, true, null],
        ['La Horde du Contrevent', 'Alain Damasio', BookCategory::FANTASY, true, null],
        ['Les Trois Mousquetaires', 'Alexandre Dumas', BookCategory::FICTION, false, null],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(SymfonyStyle $io, #[Option('Only loads the data when the table is empty')] bool $ifEmpty = false): int
    {
        if ($ifEmpty && $this->em->getRepository(Book::class)->count([]) > 0) {
            $io->note('Books already exist: nothing to do.');

            return Command::SUCCESS;
        }

        $this->em->createQuery('DELETE FROM ' . Book::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Author::class)->execute();

        /** @var array<string, Author> $authors */
        $authors = [];

        foreach (self::BOOKS as $i => [$title, $authorName, $category, $available, $summary]) {
            if (!isset($authors[$authorName])) {
                $authors[$authorName] = new Author($authorName);
                $this->em->persist($authors[$authorName]);
            }

            $book = new Book();
            $book->setTitle($title);
            $book->setAuthorName($authorName);
            $book->setAuthor($authors[$authorName]);
            $book->setCategory($category);
            $book->setAvailable($available);
            $book->setSummary($summary);
            $book->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days', $i * 3)));
            $this->em->persist($book);
        }
        $this->em->flush();

        $io->success(\sprintf('%d authors and %d books loaded.', \count($authors), \count(self::BOOKS)));

        return Command::SUCCESS;
    }
}
