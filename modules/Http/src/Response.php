<?php
namespace Http;

class Response
{
	/**
	 * @var Application
	 */
	private $app;
	
	/**
	 * @var string[]
	 */
	private $headers = [];
	
	/**
	 * @var string
	 */
	private $body;
	
	/**
	 * Constructor
	 * 
	 * @param \Http\Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}
	
	/**
	 * Add a header string to the response
	 * 
	 * @param string $header
	 */
	public function header(string $header)
	{
		$this->headers[] = $header;
	}
	
	/**
	 * Get or add headers to the response
	 * 
	 * @param array $headers
	 * @return array
	 */
	public function headers(array $headers = null) : array
	{
		if ($headers !== null) {
			foreach ($headers as $header) {
				$this->header($header);
			}
		}
		return $this->headers;
	}
	
	/**
	 * Set the HTTP response code
	 * 
	 * @param int $code
	 */
	public function code(int $code)
	{
		http_response_code($code);
	}
	
	/**
	 * Get or set the response's body
	 * 
	 * @param string $body
	 * @return string
	 */
	public function body(string $body = null) : string 
	{
		if ($body !== null) {
			$this->body = $body;
		}
		return $this->body;
	}
	
	/**
	 * Output the response's body
	 */
	public function output()
	{
		foreach ($this->headers as $header) {
			header($header);
		}
		echo $this->body;
		
		$this->app->stop();
	}
}