<?php
/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 12/2/23
 * Time: 16:13
 */

namespace AutoGamesDiscountCreator\Core\WordPress;

/**
 * A class for working with WordPress functions.
 */
class WordPressFunctions
{
	private $class;

	/**
	 * WordPressFunctions constructor.
	 *
	 * @param $class
	 */
	public function __construct($class)
	{
		$this->class = $class;
	}

	/**
	 * Adds an action or filter hook.
	 *
	 * @param string $hook The name of the action or filter to which the $function_to_add is hooked.
	 * @param string $method The callback function to be executed when the action or filter is triggered or applied.
	 * @param int $priority The priority at which the function should be executed.
	 * @param int $acceptedArguments The number of arguments the function accepts.
	 */
	public function addHook(
		string $hook,
		string $method,
		int $priority = 10,
		int $acceptedArguments = 1
	): void {
		\add_filter($hook, [$this->class, $method], $priority, $acceptedArguments);
	}

	/**
	 * Schedules an event to occur at the specified time and interval.
	 *
	 * @param string $hook The hook name of the event.
	 * @param string $interval The interval at which the event should occur. Can be 'hourly', 'twicedaily', or 'daily'.
	 * @param string $method
	 * @param int|null $timestamp The UNIX timestamp of the time the event should occur.
	 * @param int $priority
	 * @param int $acceptedArguments
	 */
	public function scheduleEvent(
		string $hook,
		string $interval,
		string $method,
		int $timestamp = null,
		int $priority = 10,
		int $acceptedArguments = 1
	): void {
		if (!$timestamp) {
			$timestamp = time();
		}
		$this->addHook($hook, $method, $priority, $acceptedArguments);
		if (!\wp_next_scheduled($hook)) {
			\wp_schedule_event($timestamp, $interval, $hook);
		}
	}

	/**
	 * Sets a transient.
	 *
	 * @param string $transientName The name of the transient.
	 * @param mixed $value The value to be set as the transient.
	 * @param int $expiration The number of seconds after which the transient should expire.
	 */
	public function setTransient(string $transientName, $value, int $expiration): void
	{
		\set_transient($transientName, $value, $expiration);
	}

	/**
	 * Gets the value of a transient.
	 *
	 * @param string $transientName The name of the transient.
	 *
	 * @return mixed|false The value of the transient, or false if the transient does not exist or has expired.
	 */
	public function getTransient(string $transientName)
	{
		return \get_transient($transientName);
	}

	/**
	 * Create a new post
	 *
	 * @param array $post_data The post data.
	 *
	 * @return int|WP_Error The ID of the new post on success, WP_Error on failure.
	 */
	public function wpInsertPost(array $post_data)
	{
		return \wp_insert_post($post_data);
	}
}
