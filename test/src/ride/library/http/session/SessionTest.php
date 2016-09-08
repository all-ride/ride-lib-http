<?php

namespace ride\library\http\session;

use \PHPUnit_Framework_TestCase;

class SessionTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();

        $session = new Session($io);

        $this->assertNull($session->getId());
        $this->assertEquals(array(), $session->getAll());
        $this->assertFalse($session->isChanged());
    }

    public function testGetSet() {
        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();

        $session = new Session($io);
        $session->set('var', 'value');
        $session->set('var1', 'value1');

        $this->assertTrue($session->isChanged());

        $this->assertEquals('value1', $session->get('var1'));
        $this->assertEquals(null, $session->get('var2'));
        $this->assertEquals('default', $session->get('var2', 'default'));

        $this->assertEquals(array('var' => 'value', 'var1' => 'value1'), $session->getAll());

        $session->set('var1');

        $this->assertEquals(array('var' => 'value'), $session->getAll());
    }

    public function testRead() {
        $id = 'id';
        $data = array(
            'var1' => 'value1',
        );

        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();
        $io->expects($this->once())->method('read')->with($this->equalTo($id))->will($this->returnValue($data));

        $session = new Session($io);
        $session->read($id);

        $this->assertFalse($session->isChanged());
        $this->assertEquals($id, $session->getId());
        $this->assertEquals($data, $session->getAll());
    }

    public function testWrite() {
        $id = 'id';

        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();
        $io->expects($this->once())->method('write')->with($this->equalTo($id), $this->equalTo(array('var' => 'value', 'var2' => 'value2')));

        $session = new Session($io);
        $session->set('var', 'value');
        $session->set('var2', 'value2');

        $session->write($id);

        $this->assertFalse($session->isChanged());
    }

    /**
     * @expectedException ride\library\http\exception\HttpException
     */
    public function testWriteThrowsExceptionWhenNoIdProvided() {
        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();

        $session = new Session($io);
        $session->write();
    }

    public function testReset() {
        $id = 'id';
        $data = array(
            'var1' => 'value1',
        );

        $io = $this->getMockBuilder('ride\\library\\http\\session\\io\\SessionIO')
                   ->setMethods(array('getTimeout', 'clean', 'read', 'write'))
                   ->getMock();
        $io->expects($this->once())->method('read')->with($this->equalTo($id))->will($this->returnValue($data));

        $session = new Session($io);
        $session->reset();

        $this->assertEquals(array(), $session->getAll());
        $this->assertFalse($session->isChanged());

        $session->read($id);;

        $this->assertEquals($data, $session->getAll());
        $this->assertFalse($session->isChanged());

        $session->reset();

        $this->assertEquals(array(), $session->getAll());
        $this->assertTrue($session->isChanged());
    }

}
