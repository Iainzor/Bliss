<?php
namespace Logs\Message;

abstract class AbstractMessage
{
	/**
	 * @var string
	 */
	private $text;
	
	/**
	 * Constructor
	 * 
	 * @param string $text
	 */
	public function __construct(string $text)
	{
		$this->text = $text;
	}
	
	/**
	 * Get the text of the message
	 * 
	 * @return string
	 */
	public function text() : string
	{
		return $this->text;
	}
	
	public function __toString() {
		return $this->text;
	}
}
