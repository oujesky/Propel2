<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\Propel;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookSummary;
use Propel\Tests\Bookstore\BookSummaryQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Bookstore;
use Propel\Tests\Bookstore\BookClubList;
use Propel\Tests\Bookstore\BookClubListQuery;
use Propel\Tests\Bookstore\BookListRel;
use Propel\Tests\Bookstore\BookListRelQuery;
use Propel\Tests\Bookstore\BookListFavoriteQuery;
use Propel\Tests\Bookstore\BookstoreContest;
use Propel\Tests\Bookstore\BookstoreContestEntry;
use Propel\Tests\Bookstore\Contest;
use Propel\Tests\Bookstore\Customer;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use \DateTime;

/**
 * Tests relationships between generated Object classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is reloaded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedObjectRelTest extends BookstoreEmptyTestBase
{
    /**
     * Tests one side of a bi-directional setting of many-to-many relationships.
     */
    public function testManyToMany_Dir1()
    {
        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');
        // No save ...

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');
        // No save ...

        $this->assertCount(0, $list->getBookListRels());
        $this->assertCount(0, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());

        $xref = new BookListRel();
        $xref->setBook($book);
        $list->addBookListRel($xref);

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());

        $list->save();

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(1, BookListRelQuery::create()->find());

    }

    /**
     * Tests reverse setting of one of many-to-many relationship, with all saves cascaded.
     */
    public function testManyToMany_Dir2_Unsaved()
    {
        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');
        // No save ...

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');
        // No save (yet) ...

        $this->assertCount(0, $list->getBookListRels());
        $this->assertCount(0, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());

        $xref = new BookListRel();
        $xref->setBookClubList($list);
        $book->addBookListRel($xref);

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());
        $book->save();

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(1, BookListRelQuery::create()->find());
    }

    /**
     * Tests reverse setting of relationships, saving one of the objects first.
     * @link       http://propel.phpdb.org/trac/ticket/508
     */
    public function testManyToMany_Dir2_Saved()
    {
        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');
        $list->save();

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');
        // No save (yet) ...

        $this->assertCount(0, $list->getBookListRels());
        $this->assertCount(0, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());

        // Now set the relationship from the opposite direction.

        $xref = new BookListRel();
        $xref->setBookClubList($list);
        $book->addBookListRel($xref);

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(0, BookListRelQuery::create()->find());
        $book->save();

        $this->assertCount(1, $list->getBookListRels());
        $this->assertCount(1, $book->getBookListRels());
        $this->assertCount(1, BookListRelQuery::create()->find());

    }

    public function testManyToManyGetterExists()
    {
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\BookClubList', 'getBooks'), 'Object generator correctly adds getter for the crossRefFk');
        $this->assertFalse(method_exists('Propel\Tests\Bookstore\BookClubList', 'getBookClubLists'), 'Object generator correctly adds getter for the crossRefFk');
    }

    public function testManyToManyGetterNewObject()
    {
        $blc1 = new BookClubList();
        $books = $blc1->getBooks();
        $this->assertTrue($books instanceof ObjectCollection, 'getCrossRefFK() returns a Propel collection');
        $this->assertEquals('Book', $books->getModel(), 'getCrossRefFK() returns a collection of the correct model');
        $this->assertEquals(0, count($books), 'getCrossRefFK() returns an empty list for new objects');
        $query = BookQuery::create()
            ->filterByTitle('Harry Potter and the Order of the Phoenix');
        $books = $blc1->getBooks($query);
        $this->assertEquals(0, count($books), 'getCrossRefFK() accepts a query as first parameter');
    }

    public function testManyToManyGetter()
    {
        BookstoreDataPopulator::populate();
        $blc1 = BookClubListQuery::create()->findOneByGroupLeader('Crazyleggs');
        $books = $blc1->getBooks();
        $this->assertTrue($books instanceof ObjectCollection, 'getCrossRefFK() returns a Propel collection');
        $this->assertEquals('Book', $books->getModel(), 'getCrossRefFK() returns a collection of the correct model');
        $this->assertEquals(2, count($books), 'getCrossRefFK() returns the correct list of objects');
        $query = BookQuery::create()
            ->filterByTitle('Harry Potter and the Order of the Phoenix');
        $books = $blc1->getBooks($query);
        $this->assertEquals(1, count($books), 'getCrossRefFK() accepts a query as first parameter');
    }

    public function testManyToManyCounterExists()
    {
        $this->assertTrue(method_exists('Propel\Tests\Bookstore\BookClubList', 'countBooks'), 'Object generator correctly adds counter for the crossRefFk');
        $this->assertFalse(method_exists('Propel\Tests\Bookstore\BookClubList', 'countBookClubLists'), 'Object generator correctly adds counter for the crossRefFk');
    }

    public function testManyToManyCounterNewObject()
    {
        $blc1 = new BookClubList();
        $nbBooks = $blc1->countBooks();
        $this->assertEquals(0, $nbBooks, 'countCrossRefFK() returns 0 for new objects');
        $query = BookQuery::create()
            ->filterByTitle('Harry Potter and the Order of the Phoenix');
        $nbBooks = $blc1->countBooks($query);
        $this->assertEquals(0, $nbBooks, 'countCrossRefFK() accepts a query as first parameter');
    }

    public function testManyToManyCounter()
    {
        BookstoreDataPopulator::populate();
        $blc1 = BookClubListQuery::create()->findOneByGroupLeader('Crazyleggs');
        $nbBooks = $blc1->countBooks();
        $this->assertEquals(2, $nbBooks, 'countCrossRefFK() returns the correct list of objects');
        $query = BookQuery::create()
            ->filterByTitle('Harry Potter and the Order of the Phoenix');
        $nbBooks = $blc1->countBooks($query);
        $this->assertEquals(1, $nbBooks, 'countCrossRefFK() accepts a query as first parameter');
    }

    public function testManyToManyAdd()
    {
        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');

        $list->addBook($book);
        $this->assertEquals(1, $list->countBooks(), 'addCrossFk() sets the internal collection properly');
        $this->assertEquals(1, $list->countBookListRels(), 'addCrossFk() sets the internal cross reference collection properly');

        $list->save();
        $this->assertFalse($book->isNew(), 'related object is saved if added');
        $rels = $list->getBookListRels();
        $rel = $rels[0];
        $this->assertFalse($rel->isNew(), 'cross object is saved if added');

        $list->clearBookListRels();
        $list->clearBooks();
        $books = $list->getBooks();
        $expected = new ObjectCollection(array($book));
        $expected->setModel('Book');
        $this->assertEquals($expected, $books, 'addCrossFk() adds the object properly');
        $this->assertEquals(1, $list->countBookListRels());
    }

    /**
     * Test behavior of columns that are implicated in multiple foreign keys.
     * @link       http://propel.phpdb.org/trac/ticket/228
     */
    public function testMultiFkImplication()
    {
        BookstoreDataPopulator::populate();
        // Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry
        $b = new Bookstore();
        $b->setStoreName("Foo!");
        $b->save();

        $c = new Contest();
        $c->setName("Bookathon Contest");
        $c->save();

        $bc = new BookstoreContest();
        $bc->setBookstore($b);
        $bc->setContest($c);
        $bc->save();

        $c = new Customer();
        $c->setName("Happy Customer");
        $c->save();

        $bce = new BookstoreContestEntry();
        $bce->setBookstore($b);
        $bce->setBookstoreContest($bc);
        $bce->setCustomer($c);
        $bce->save();

        $bce->setBookstoreId(null);

        $this->assertNull($bce->getBookstoreContest());
        $this->assertNull($bce->getBookstore());
    }

    /**
     * Test the clearing of related object collection.
     * @link       http://www.propelorm.org/ticket/529
     */
    public function testClearRefFk()
    {
        BookstoreDataPopulator::populate();
        $book = new Book();
        $book->setISBN("Foo-bar-baz");
        $book->setTitle("The book title");

        // No save ...

        $r = new Review();
        $r->setReviewedBy('Me');
        $r->setReviewDate(new DateTime("now"));

        $book->addReview($r);

        // No save (yet) ...

        $this->assertEquals(1, count($book->getReviews()) );
        $book->clearReviews();
        $this->assertEquals(0, count($book->getReviews()));
    }

    /**
     * Test the clearing of related object collection via a many-to-many association.
     * @link       http://www.propelorm.org/ticket/1374
     */
    public function testClearCrossFk()
    {
        $book = new Book();
        $bookClub = new BookClubList();
        $book->addBookClubList($bookClub);
        $this->assertEquals(1, count($book->getBookClubLists()));
        $book->clear();
        $this->assertEquals(0, count($book->getBookClubLists()));
    }

    /**
     * This tests to see whether modified objects are being silently overwritten by calls to fk accessor methods.
     * @link       http://propel.phpdb.org/trac/ticket/509#comment:5
     */
    public function testModifiedObjectOverwrite()
    {
        BookstoreDataPopulator::populate();
        $author = new Author();
        $author->setFirstName("John");
        $author->setLastName("Public");

        $books = $author->getBooks(); // empty, of course
        $this->assertEquals(0, count($books), "Expected empty collection.");

        $book = new Book();
        $book->setTitle("A sample book");
        $book->setISBN("INITIAL ISBN");

        $author->addBook($book);

        $author->save();

        $book->setISBN("MODIFIED ISBN");

        $books = $author->getBooks();
        $this->assertEquals(1, count($books), "Expected 1 book.");
        $this->assertSame($book, $books[0], "Expected the same object to be returned by fk accessor.");
        $this->assertEquals("MODIFIED ISBN", $books[0]->getISBN(), "Expected the modified value NOT to have been overwritten.");
    }

    public function testFKGetterUseInstancePool()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $author = AuthorQuery::create()->findOne($con);
        // populate book instance pool
        $books = $author->getBooks(null, $con);
        $sql = $con->getLastExecutedQuery();
        $author = $books[0]->getAuthor($con);
        $this->assertEquals($sql, $con->getLastExecutedQuery(), 'refFK getter uses instance pool if possible');
    }

    public function testRefFKGetJoin()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        PublisherTableMap::clearInstancePool();
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $author = AuthorQuery::create()->findOne($con);
        // populate book instance pool
        $books = $author->getBooksJoinPublisher(null, $con);
        $sql = $con->getLastExecutedQuery();
        $publisher = $books[0]->getPublisher($con);
        $this->assertEquals($sql, $con->getLastExecutedQuery(), 'refFK getter uses instance pool if possible');
    }

    public function testRefFKAddReturnsCurrentObject()
    {
        $author = new Author();
        $author->setFirstName('Leo');
        $ret = $author->addBook(new Book());
        $this->assertSame($author, $ret);
    }

    public function testSetterCollection()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $books = new ObjectCollection();
        for ($i = 0; $i < 10; $i++) {
            $b = new Book();
            $b->setTitle('My Book ' . $i);
            $b->setIsbn($i);

            $books[] = $b;
        }
        $this->assertEquals(10, $books->count());

        // Basic usage
        $bookClubList1 = new BookClubList();
        $bookClubList1->setGroupLeader('BookClubList1 Leader');
        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(10, $bookClubList1->getBooks()->count());
        $this->assertEquals(1,  BookClubListQuery::create()->count());
        $this->assertEquals(10, BookQuery::create()->count());
        $this->assertEquals(10, BookListRelQuery::create()->count());

        $i = 0;
        foreach ($bookClubList1->getBooks() as $book) {
            $this->assertEquals('My Book ' . $i, $book->getTitle());
            $this->assertEquals($i++, $book->getIsbn());
        }

        // Remove an element
        $books->shift();
        $this->assertEquals(9, $books->count());

        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(9, $bookClubList1->getBooks()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(9, BookListRelQuery::create()->count());
        $this->assertEquals(10, BookQuery::create()->count());

        // Add a new object
        $newBook = new Book();
        $newBook->setTitle('My New Book');
        $newBook->setIsbn(1234);

        // Kind of new collection
        $books   = clone $books;
        $books[] = $newBook;

        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(10, $books->count());
        $this->assertEquals(10, $bookClubList1->getBooks()->count());
        $this->assertEquals(1,  BookClubListQuery::create()->count());
        $this->assertEquals(10, BookListRelQuery::create()->count());
        $this->assertEquals(11, BookQuery::create()->count());

        // Add a new object
        $newBook1 = new Book();
        $newBook1->setTitle('My New Book1');
        $newBook1->setIsbn(1256);

        // Existing collection - The fix around reference is tested here.
        // Ths `$books` collection has ever been setted to the `$bookClubList1` object.
        // Here we are adding a new object into the collection but, in this process, it
        // added the new object in the internal `collBooks` of the `$bookClubList1`
        // object too.
        // That's why the new object is not tracked and the cross object is not created,
        // in `addBook()` we consider the `collBooks` ever contains this new object. It's
        // not true but this is the "reference" process.
        // By saying "all new objects have to be added", we solve this issue. To know if
        // it's the best solution is the question.
        $books[] = $newBook1;

        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(11, $books->count());
        $this->assertEquals(11, $bookClubList1->getBooks()->count());
        $this->assertEquals(1,  BookClubListQuery::create()->count());
        $this->assertEquals(11, BookListRelQuery::create()->count());
        $this->assertEquals(12, BookQuery::create()->count());

        // Add the same collection
        $books = $bookClubList1->getBooks();

        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(11, $books->count());
        $this->assertEquals(11, $bookClubList1->getBooks()->count());
        $this->assertEquals(1,  BookClubListQuery::create()->count());
        $this->assertEquals(11, BookListRelQuery::create()->count());
        $this->assertEquals(12, BookQuery::create()->count());
    }

    public function testSetterCollectionWithNoData()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $books = new ObjectCollection();
        $this->assertEquals(0, $books->count());

        // Basic usage
        $bookClubList1 = new BookClubList();
        $bookClubList1->setGroupLeader('BookClubList1 Leader');
        $bookClubList1->setBooks($books);
        $bookClubList1->save();

        $this->assertEquals(0, $bookClubList1->getBooks()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(0, BookQuery::create()->count());
        $this->assertEquals(0, BookListRelQuery::create()->count());
    }

    public function testSetterCollectionSavesForeignObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $book = new Book();
        $book->setTitle('My Book');
        $book->setISBN('FA404');
        $book->save();

        // Modify it but don't save it
        $book->setTitle('My Title');

        $coll = new ObjectCollection();
        $coll[] = $book;

        BookTableMap::clearInstancePool();
        $book = BookQuery::create()->findPk($book->getPrimaryKey());

        $bookClubList1 = new BookClubList();
        $bookClubList1->setGroupLeader('fabpot');
        $bookClubList1->setBooks($coll);
        $bookClubList1->save();

        $this->assertEquals(1, $bookClubList1->getBooks()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(1, BookListRelQuery::create()->count());

        $result = BookQuery::create()
            ->filterById($book->getId())
            ->select('Title')
            ->findOne();
        $this->assertSame('My Title', $result);
    }

    public function testSetterCollectionWithNewObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $coll = new ObjectCollection();
        $coll->setModel('Book');

        for ($i = 0; $i < 3; $i++) {
            $b = new Book();
            $b->setTitle('Title ' . $i);
            $b->setIsbn('1245' . $i);

            $coll[] = $b;
        }

        $bookClubList = new BookClubList();
        $bookClubList->setGroupLeader('fabpot');
        $bookClubList->setBooks($coll);
        $bookClubList->save();

        $this->assertEquals(3, $coll->count());
        $this->assertEquals(3, count($bookClubList->getBooks()));
        $this->assertSame($coll, $bookClubList->getBooks());
        $this->assertEquals(3, BookQuery::create()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(3, BookListRelQuery::create()->count());
    }

    public function testSetterCollectionWithExistingObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        for ($i = 0; $i < 3; $i++) {
            $b = new Book();
            $b->setTitle('Book ' . $i);
            $b->setIsbn($i);
            $b->save();
        }

        BookTableMap::clearInstancePool();
        $books = BookQuery::create()->find();

        $bookClubList = new BookClubList();
        $bookClubList->setGroupLeader('fabpot');
        $bookClubList->setBooks($books);
        $bookClubList->save();

        $this->assertEquals(3, count($bookClubList->getBooks()));
        $this->assertEquals(3, BookQuery::create()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(3, BookListRelQuery::create()->count());

        $i = 0;
        foreach ($bookClubList->getBooks() as $book) {
            $this->assertEquals('Book ' . $i++, $book->getTitle());
        }
    }

    public function testSetterCollectionWithEmptyCollection()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $bookClubList = new BookClubList();
        $bookClubList->setGroupLeader('fabpot');
        $bookClubList->setBooks(new ObjectCollection());
        $bookClubList->save();

        $this->assertEquals(0, count($bookClubList->getBooks()));

        $this->assertEquals(0, BookQuery::create()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(0, BookListRelQuery::create()->count());
    }

    public function testSetterCollectionReplacesOldObjectsByNewObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $books = new ObjectCollection();
        foreach (array('foo', 'bar') as $title) {
            $b = new Book();
            $b->setTitle($title);
            $b->setIsbn('FA404');

            $books[] = $b;
        }

        $bookClubList = new BookClubList();
        $bookClubList->setGroupLeader('fabpot');
        $bookClubList->setBooks($books);
        $bookClubList->save();

        $books = $bookClubList->getBooks();
        $this->assertEquals('foo', $books[0]->getTitle());
        $this->assertEquals('bar', $books[1]->getTitle());

        $books = new ObjectCollection();
        foreach (array('bam', 'bom') as $title) {
            $b = new Book();
            $b->setTitle($title);
            $b->setIsbn('FA404');

            $books[] = $b;
        }

        $bookClubList->setBooks($books);
        $bookClubList->save();

        $books = $bookClubList->getBooks();
        $this->assertEquals('bam', $books[0]->getTitle());
        $this->assertEquals('bom', $books[1]->getTitle());

        $this->assertEquals(1, BookClubListQuery::create()->count());
        $this->assertEquals(2, BookListRelQuery::create()->count());

        // ensure we have valid "association" objects
        $this->assertEquals(1, BookListRelQuery::create()
            ->filterByBookClubList($bookClubList)
            ->filterByBook($books[0])
            ->count()
        );
        $this->assertEquals(1, BookListRelQuery::create()
            ->filterByBookClubList($bookClubList)
            ->filterByBook($books[1])
            ->count()
        );

        $this->assertEquals(4, BookQuery::create()->count());
    }

    public function testSetterCollectionWithCustomNamedFKs()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();
        BookListFavoriteQuery::create()->deleteAll();

        $books = new ObjectCollection();
        foreach (array('foo', 'bar', 'test') as $title) {
            $b = new Book();
            $b->setTitle($title);
            $b->setIsbn('FA404');

            $books[] = $b;
        }

        $bookClubList = new BookClubList();
        $bookClubList->setGroupLeader('fabpot');
        $bookClubList->setFavoriteBooks($books);
        $bookClubList->save();

        $bookClubList->reload(true);

        $this->assertCount(3, $bookClubList->getFavoriteBooks());

        $bookClubList->reload(true);

        $books->shift();

        $bookClubList->setFavoriteBooks($books);
        $bookClubList->save();

        $bookClubList->reload(true);

        $this->assertCount(2, $bookClubList->getFavoriteBooks());
    }

    public function testSetterCollectionWithManyToManyModifiedByReferenceWithANewObject()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $book = new Book();
        $book->setTitle('foo');

        // The object is "new"
        $this->assertTrue($book->isNew());

        $bookClubList = new BookClubList();
        $books = $bookClubList->getBooks();
        // Add the object by reference
        $books[] = $book;

        $bookClubList->setBooks($books);
        $bookClubList->save();

        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(1, BookListRelQuery::create()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
    }

    public function testSetterCollectionWithManyToManyModifiedByReferenceWithAnExistingObject()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();
        BookListRelQuery::create()->deleteAll();

        $book = new Book();
        $book->setTitle('foo');
        $book->save();

        // The object isn't "new"
        $this->assertFalse($book->isNew());

        $bookClubList = new BookClubList();
        $books = $bookClubList->getBooks();
        // Add the object by reference
        $books[] = $book;

        $bookClubList->setBooks($books);
        $bookClubList->save();

        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(1, BookListRelQuery::create()->count());
        $this->assertEquals(1, BookClubListQuery::create()->count());
    }

    public function testRemoveObjectFromCollection()
    {
        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');

        $list2 = new BookClubList();
        $list2->setGroupLeader('FooBar group');
        // No save ...

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');
        // No save ...
        $this->assertCount(0, $book->getBookClubLists(), 'No BookClubList');

        $book->addBookClubList($list);
        $book->addBookClubList($list2);
        $this->assertCount(2, $book->getBookClubLists(), 'Two BookClubList');

        $book->removeBookClubList($list);
        $this->assertCount(1, $book->getBookClubLists(), 'One BookClubList has been remove');
    }

    public function testRemoveObjectStoredInDBFromCollection()
    {
        BookQuery::create()->deleteAll();
        BookClubListQuery::create()->deleteAll();

        $list = new BookClubList();
        $list->setGroupLeader('Archimedes Q. Porter');

        $list2 = new BookClubList();
        $list2->setGroupLeader('FooBar group');
        // No save ...

        $book = new Book();
        $book->setTitle( "Jungle Expedition Handbook" );
        $book->setISBN('TEST');
        $book->addBookClubList($list);
        $book->addBookClubList($list2);
        $book->save();

        $this->assertEquals(2, BookClubListQuery::create()->count(), 'Two BookClubList');
        $this->assertEquals(2, BookListRelQuery::create()->count(), 'Two BookClubList');

        $book->removeBookClubList($list);
        $this->assertEquals(2, BookListRelQuery::create()->count(), 'still Two BookClubList in db before save()');
        $this->assertCount(1, $book->getBookClubLists(), 'One BookClubList has been remove');
        $book->save();

        $this->assertCount(1, $book->getBookClubLists(), 'One BookClubList has been remove');
        $this->assertEquals(1, BookListRelQuery::create()->count(), 'One BookClubList has been remove');
    }

    public function testRemoveObjectOneToMany()
    {
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $book = new Book();
        $book->setTitle('Propel Book');

        $book2 = new Book();
        $book2->setTitle('Propel2 Book');

        $author = new Author();
        $author->setFirstName('François');
        $author->setLastName('Z');

        $author->addBook($book);
        $author->addBook($book2);

        $this->assertCount(2, $author->getBooks());

        $author->removeBook($book);

        $books = $author->getBooks();
        $this->assertCount(1, $books);
        $this->assertEquals('Propel2 Book', reset($books)->getTitle());

        $author->save();
        $book->save();
        $book2->save();

        $this->assertEquals(2, BookQuery::create()->count(), 'Two Book');
        $this->assertEquals(1, AuthorQuery::create()->count(), 'One Author');
        $this->assertEquals(1, BookQuery::create()->filterByAuthor($author)->count());

        $author->addBook($book);
        $author->save();

        $this->assertEquals(2, BookQuery::create()->filterByAuthor($author)->count());

        $author->removeBook($book2);
        $author->save();

        $this->assertEquals(1, BookQuery::create()->filterByAuthor($author)->count());
        $this->assertEquals(2, BookQuery::create()->count(), 'Two Book because FK is not required so book is not delete when removed from author\'s book collection');
    }

    public function testRemoveObjectOneToManyWithFkRequired()
    {
        BookSummaryQuery::create()->deleteAll();
        BookQuery::create()->deleteAll();

        $bookSummary = new BookSummary();
        $bookSummary->setSummary('summary Propel Book');

        $bookSummary2 = new BookSummary();
        $bookSummary2->setSummary('summary2 Propel Book');

        $book = new Book();
        $book->setTitle('Propel Book');

        $book->addBookSummary($bookSummary);
        $book->addBookSummary($bookSummary2);

        $this->assertCount(2, $book->getBookSummaries());

        $book->removeBookSummary($bookSummary);

        $bookSummaries = $book->getBookSummaries();
        $this->assertCount(1, $bookSummaries);
        $this->assertEquals('summary2 Propel Book', reset($bookSummaries)->getSummary());

        $book->save();
        $bookSummary2->save();

        $this->assertEquals(1, BookQuery::create()->count(), 'One Book');
        $this->assertEquals(1, BookSummaryQuery::create()->count(), 'One Summary');
        $this->assertEquals(1, BookSummaryQuery::create()->filterBySummarizedBook($book)->count());

        $book->addBookSummary($bookSummary);
        $bookSummary->save();
        $book->save();

        $this->assertEquals(2, BookSummaryQuery::create()->filterBySummarizedBook($book)->count());

        $book->removeBookSummary($bookSummary2);
        $book->save();

        $this->assertEquals(1, BookSummaryQuery::create()->filterBySummarizedBook($book)->count());
        $this->assertEquals(1, BookSummaryQuery::create()->count(), 'One Book summary because FK is required so book summary is deleted when book is saved');
    }
}
