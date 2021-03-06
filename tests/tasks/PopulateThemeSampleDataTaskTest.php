<?php

class PopulateThemeSampleDataTaskTest extends SapphireTest
{
	protected $usesDatabase = true;

	/**
	 * Ensure that the "contact" user form is only created once
	 */
	public function testOnlyCreateContactFormOnce()
	{
		$createdMessage = 'Created "contact" UserDefinedForm';

		$task = new PopulateThemeSampleDataTask;

		// Run the task
		$this->assertContains($createdMessage, $this->bufferedTask($task));

		// Run a second time
		$this->assertNotContains($createdMessage, $this->bufferedTask($task));

		// Change the page name
		$form = UserDefinedForm::get()->filter('URLSegment', 'contact')->first();
		$form->URLSegment = 'testing';
		$form->write();

		// Ensure the old version is still detected in draft, so not recreated
		$this->assertNotContains($createdMessage, $this->bufferedTask($task));

		// Delete the page, then ensure it's recreated again (DataObject::delete will remove staged versions)
		$form->delete();
		$this->assertContains($createdMessage, $this->bufferedTask($task));
	}

	/**
	 * Run a BuildTask while buffering its output, and return the result
	 *
	 * @param  BuildTask $task
	 * @return string
	 */
	protected function bufferedTask(BuildTask $task)
	{
		ob_start();
		$task->run(new SS_HTTPRequest('GET', '/'));
		return ob_get_clean();
	}
}
