<?php

namespace AutoGamesDiscountCreator\Core\Settings;

class RuntimeStateRepository
{
	public function getAll(): array
	{
		$state = [];

		if (function_exists('get_option')) {
			$state = get_option(AGDC_RUNTIME_STATE_OPTION, []);
		}

		if (!is_array($state)) {
			$state = [];
		}

		return array_merge(
			[
				'last_run' => [],
				'last_error' => [],
				'last_test' => [],
			],
			$state
		);
	}

	public function markRunStart(string $task): void
	{
		$this->updateTaskState(
			'task:' . $task,
			[
				'status' => 'running',
				'started_at' => current_time('mysql'),
			]
		);
	}

	public function markRunSuccess(string $task, array $meta = []): void
	{
		$state = $this->getAll();
		$key = 'task:' . $task;
		$existing = $state['last_run'][$key] ?? [];
		$state['last_run'][$key] = array_merge(
			$existing,
			[
				'status' => 'success',
				'finished_at' => current_time('mysql'),
			],
			$meta
		);

		if (($state['last_error']['task'] ?? null) === $task) {
			$state['last_error'] = [];
		}

		update_option(AGDC_RUNTIME_STATE_OPTION, $state, false);
	}

	public function markRunFailure(string $task, string $message, array $meta = []): void
	{
		$state = $this->getAll();
		$payload = array_merge(
			[
				'task' => $task,
				'message' => $message,
				'at' => current_time('mysql'),
			],
			$meta
		);

		$state['last_error'] = $payload;
		$state['last_run']['task:' . $task] = array_merge(
			[
				'status' => 'error',
				'finished_at' => current_time('mysql'),
			],
			$meta
		);

		update_option(AGDC_RUNTIME_STATE_OPTION, $state, false);
	}

	public function markTestResult(string $status, string $message, array $meta = []): void
	{
		$state = $this->getAll();
		$state['last_test'] = array_merge(
			[
				'status' => $status,
				'message' => $message,
				'at' => current_time('mysql'),
			],
			$meta
		);

		if ($status === 'error') {
			$state['last_error'] = [
				'task' => 'manual_test_fetch',
				'message' => $message,
				'at' => current_time('mysql'),
			];
		} elseif (($state['last_error']['task'] ?? null) === 'manual_test_fetch') {
			$state['last_error'] = [];
		}

		update_option(AGDC_RUNTIME_STATE_OPTION, $state, false);
	}

	private function updateTaskState(string $key, array $data): void
	{
		$state = $this->getAll();
		$existing = $state['last_run'][$key] ?? [];
		$state['last_run'][$key] = array_merge($existing, $data);
		update_option(AGDC_RUNTIME_STATE_OPTION, $state, false);
	}
}
