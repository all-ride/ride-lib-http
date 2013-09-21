<?php

namespace pallo\library\http\session;

use \PHPUnit_Framework_TestCase;

class SessionTest extends PHPUnit_Framework_TestCase {

	public function testSession() {
		$id = 'id';
		$data = array(
			'var1' => 'value1',
		);

		$io = $this->getMock('pallo\\library\\http\\session\\io\\SessionIO', array('clean', 'read', 'write'));
		$io->expects($this->once())->method('read')->with($this->equalTo($id))->will($this->returnValue($data));
		$io->expects($this->once())->method('write')->with($this->equalTo($id), $this->equalTo(array('var3' => 'value3')));

		$session = new Session($io);

		$this->assertNull($session->getId());

		$session->read($id);

		$this->assertEquals($id, $session->getId());
		$this->assertEquals($data, $session->getAll());
		$this->assertEquals('value1', $session->get('var1'));
		$this->assertEquals(null, $session->get('var2'));
		$this->assertEquals('default', $session->get('var3', 'default'));

		$session->set('var2', 'value2');
		$session->set('var1');

		$this->assertEquals(array('var2' => 'value2'), $session->getAll());

		$session->reset();

		$this->assertEquals(array(), $session->getAll());

		$session->set('var3', 'value3');
		$session->write();
	}

}