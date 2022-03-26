<?php

namespace OCA\Diary\Tests\Unit\Controller;

use Exception;
use OCA\Diary\Db\Entry;
use OCA\Diary\Db\EntryMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\TemplateResponse;

use OCA\Diary\Controller\PageController;


class PageControllerTest extends TestCase
{
    /** @var PageController */
    private $controller;
    private $userId = 'john';
    /** @var EntryMapper|MockObject */
    private $mapper;

    public function setUp(): void
    {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $this->mapper = $this->getMockBuilder(EntryMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new PageController(
            'diary', $request, $this->userId, $this->mapper
        );
    }

    public function testIndex()
    {
        $result = $this->controller->index();

        $this->assertEquals('index', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }

    public function testGetEntry()
    {
        $entryDate = "2022-08-07";
        $entryContent = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam";
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->userId),
                $this->equalTo($entryDate))
            ->will($this->returnValue($entry));
        $result = $this->controller->getEntry($entryDate);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals($entry, $result->getData());
    }

    public function testNotFound()
    {
        $entryDate = "2022-08-07";
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->userId),
                $this->equalTo($entryDate))
            ->will($this->throwException(new DoesNotExistException("Id not found")));
        $result = $this->controller->getEntry($entryDate);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals(["isEmpty" => true], $result->getData());
    }

    public function testUpdateEntry()
    {
        $entryDate = "2022-08-07";
        $entryContent = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam";
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('insertOrUpdate')
            ->with($this->equalTo($entry))
            ->will($this->returnValue($entry));
        $result = $this->controller->updateEntry($entryDate, $entryContent);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals($entry, $result->getData());
    }

    public function testUpdateEntryFailure()
    {
        $this->expectException(Exception::class);
        $entryDate = "2022-08-07";
        $entryContent = "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam";
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('insertOrUpdate')
            ->with($this->equalTo($entry))
            ->will($this->throwException(new Exception("Some error while updating")));
        $result = $this->controller->updateEntry($entryDate, $entryContent);
        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $result->getStatus());
        $this->assertEquals(['error' => "Some error while updating"], $result->getData());
    }

    /**
     * Create an Entry element
     * @param string $date
     * @param string $userId
     * @param string $content
     * @return Entry
     */
    private function createMockEntry(string $date, string $userId, string $content): Entry
    {
        $entry = new Entry();
        $entry->setId($this->userId . $date);
        $entry->setUid($this->userId);
        $entry->setEntryDate($date);
        $entry->setEntryContent($content);
        return $entry;

    }

}
