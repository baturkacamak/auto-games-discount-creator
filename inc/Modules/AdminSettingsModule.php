<?php

namespace AutoGamesDiscountCreator\Modules;

use AutoGamesDiscountCreator\Core\Module\AbstractModule;
use AutoGamesDiscountCreator\Core\Settings\RuntimeStateRepository;
use AutoGamesDiscountCreator\Core\Settings\SettingsRepository;
use AutoGamesDiscountCreator\Core\Utility\GameInformationDatabase;
use AutoGamesDiscountCreator\Core\Utility\OfferSelectionService;
use AutoGamesDiscountCreator\Core\Utility\Scraper;
use AutoGamesDiscountCreator\Modules\ScheduleModule;
use Throwable;

class AdminSettingsModule extends AbstractModule
{
	private SettingsRepository $settingsRepository;
	private RuntimeStateRepository $runtimeStateRepository;
	private OfferSelectionService $offerSelectionService;

	public function __construct($wpFunctions = null)
	{
		parent::__construct($wpFunctions);
		$this->settingsRepository = new SettingsRepository();
		$this->runtimeStateRepository = new RuntimeStateRepository();
		$this->offerSelectionService = new OfferSelectionService();
	}

	public function setup()
	{
		$this->wpFunctions->addHook('admin_menu', 'registerAdminPage');
		$this->wpFunctions->addHook('admin_init', 'registerSettings');
		$this->wpFunctions->addHook('admin_post_agdc_test_fetch', 'handleTestFetch');
		$this->wpFunctions->addHook('admin_post_agdc_run_task', 'handleRunTask');
		$this->wpFunctions->addHook('admin_post_agdc_cleanup_drafts', 'handleCleanupDrafts');
		$this->wpFunctions->addHook('plugin_action_links_' . plugin_basename(AGDC_PLUGIN_FILE), 'addPluginActionLinks');
	}

	public function registerAdminPage(): void
	{
		add_options_page(
			__('Auto Games Discount Creator', 'auto-games-discount-creator'),
			__('Auto Games Discount Creator', 'auto-games-discount-creator'),
			'manage_options',
			'auto-games-discount-creator',
			[$this, 'renderSettingsPage']
		);
	}

	public function registerSettings(): void
	{
		register_setting(
			'agdc_settings_group',
			AGDC_SETTINGS_OPTION,
			[
				'sanitize_callback' => [$this, 'sanitizeSettings'],
				'default' => $this->settingsRepository->getDefaults(),
			]
		);

		add_settings_section(
			'agdc_general_section',
			__('General', 'auto-games-discount-creator'),
			[$this, 'renderGeneralSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_enabled',
			__('Enable plugin automation', 'auto-games-discount-creator'),
			[$this, 'renderEnabledField'],
			'auto-games-discount-creator',
			'agdc_general_section'
		);

		add_settings_field(
			'agdc_dry_run',
			__('Dry run mode', 'auto-games-discount-creator'),
			[$this, 'renderDryRunField'],
			'auto-games-discount-creator',
			'agdc_general_section'
		);

		add_settings_section(
			'agdc_posting_section',
			__('Posting', 'auto-games-discount-creator'),
			[$this, 'renderPostingSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_author_id',
			__('Default post author', 'auto-games-discount-creator'),
			[$this, 'renderAuthorField'],
			'auto-games-discount-creator',
			'agdc_posting_section'
		);

		add_settings_field(
			'agdc_post_status',
			__('Default post status', 'auto-games-discount-creator'),
			[$this, 'renderPostStatusField'],
			'auto-games-discount-creator',
			'agdc_posting_section'
		);

		add_settings_field(
			'agdc_daily_post_time',
			__('Daily post time', 'auto-games-discount-creator'),
			[$this, 'renderDailyPostTimeField'],
			'auto-games-discount-creator',
			'agdc_posting_section'
		);

		add_settings_section(
			'agdc_daily_posting_section',
			__('Daily Posting', 'auto-games-discount-creator'),
			[$this, 'renderDailyPostingSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_daily_author_id',
			__('Daily roundup author', 'auto-games-discount-creator'),
			[$this, 'renderDailyAuthorField'],
			'auto-games-discount-creator',
			'agdc_daily_posting_section'
		);

		add_settings_field(
			'agdc_daily_post_status',
			__('Daily roundup status', 'auto-games-discount-creator'),
			[$this, 'renderDailyPostStatusField'],
			'auto-games-discount-creator',
			'agdc_daily_posting_section'
		);

		add_settings_section(
			'agdc_free_posting_section',
			__('Free Game Posting', 'auto-games-discount-creator'),
			[$this, 'renderFreePostingSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_free_author_id',
			__('Free-game author', 'auto-games-discount-creator'),
			[$this, 'renderFreeAuthorField'],
			'auto-games-discount-creator',
			'agdc_free_posting_section'
		);

		add_settings_field(
			'agdc_free_post_status',
			__('Free-game status', 'auto-games-discount-creator'),
			[$this, 'renderFreePostStatusField'],
			'auto-games-discount-creator',
			'agdc_free_posting_section'
		);

		add_settings_section(
			'agdc_data_model_section',
			__('Data Model', 'auto-games-discount-creator'),
			[$this, 'renderDataModelSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_default_market_target_key',
			__('Default market target', 'auto-games-discount-creator'),
			[$this, 'renderDefaultMarketTargetField'],
			'auto-games-discount-creator',
			'agdc_data_model_section'
		);

		add_settings_field(
			'agdc_default_discount_store_key',
			__('Default discount store', 'auto-games-discount-creator'),
			[$this, 'renderDefaultDiscountStoreField'],
			'auto-games-discount-creator',
			'agdc_data_model_section'
		);

		add_settings_field(
			'agdc_default_free_store_key',
			__('Default free-game store', 'auto-games-discount-creator'),
			[$this, 'renderDefaultFreeStoreField'],
			'auto-games-discount-creator',
			'agdc_data_model_section'
		);

		add_settings_field(
			'agdc_daily_repeat_window_days',
			__('Daily repeat window (days)', 'auto-games-discount-creator'),
			[$this, 'renderDailyRepeatWindowField'],
			'auto-games-discount-creator',
			'agdc_data_model_section'
		);

		add_settings_field(
			'agdc_free_repeat_window_days',
			__('Free-game repeat window (days)', 'auto-games-discount-creator'),
			[$this, 'renderFreeRepeatWindowField'],
			'auto-games-discount-creator',
			'agdc_data_model_section'
		);

		add_settings_section(
			'agdc_source_section',
			__('Source', 'auto-games-discount-creator'),
			[$this, 'renderSourceSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_itad_session_token',
			__('ITAD session token override', 'auto-games-discount-creator'),
			[$this, 'renderItadSessionTokenField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_itad_session_cookie',
			__('ITAD sess2 cookie override', 'auto-games-discount-creator'),
			[$this, 'renderItadSessionCookieField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_itad_visitor_cookie',
			__('ITAD visitor cookie override', 'auto-games-discount-creator'),
			[$this, 'renderItadVisitorCookieField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_itad_country_code',
			__('ITAD country code', 'auto-games-discount-creator'),
			[$this, 'renderItadCountryCodeField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_itad_currency_code',
			__('ITAD currency code', 'auto-games-discount-creator'),
			[$this, 'renderItadCurrencyCodeField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_daily_payloads_json',
			__('Daily payloads JSON', 'auto-games-discount-creator'),
			[$this, 'renderDailyPayloadsField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_field(
			'agdc_hourly_payloads_json',
			__('Hourly payloads JSON', 'auto-games-discount-creator'),
			[$this, 'renderHourlyPayloadsJsonField'],
			'auto-games-discount-creator',
			'agdc_source_section'
		);

		add_settings_section(
			'agdc_debug_section',
			__('Debug', 'auto-games-discount-creator'),
			[$this, 'renderDebugSection'],
			'auto-games-discount-creator'
		);

		add_settings_field(
			'agdc_last_run',
			__('Last run', 'auto-games-discount-creator'),
			[$this, 'renderLastRunField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);

		add_settings_field(
			'agdc_next_run',
			__('Next run', 'auto-games-discount-creator'),
			[$this, 'renderNextRunField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);

		add_settings_field(
			'agdc_last_error',
			__('Last error', 'auto-games-discount-creator'),
			[$this, 'renderLastErrorField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);

		add_settings_field(
			'agdc_test_fetch',
			__('Test fetch', 'auto-games-discount-creator'),
			[$this, 'renderTestFetchField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);

		add_settings_field(
			'agdc_run_tasks',
			__('Run tasks', 'auto-games-discount-creator'),
			[$this, 'renderRunTasksField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);

		add_settings_field(
			'agdc_cleanup_drafts',
			__('Cleanup drafts', 'auto-games-discount-creator'),
			[$this, 'renderCleanupDraftsField'],
			'auto-games-discount-creator',
			'agdc_debug_section'
		);
	}

	public function sanitizeSettings($input): array
	{
		$settings = $this->settingsRepository->getAll();
		$input = is_array($input) ? $input : [];

		$settings['general']['enabled'] = !empty($input['general']['enabled']);
		$settings['general']['dry_run'] = !empty($input['general']['dry_run']);

		$settings['posting']['author_id'] = max(1, absint($input['posting']['author_id'] ?? 1));
		$settings['posting']['category_id'] = max(0, absint($input['posting']['category_id'] ?? 0));
		$settings['posting']['tags'] = sanitize_text_field($input['posting']['tags'] ?? '');
		$post_status = sanitize_key($input['posting']['post_status'] ?? 'draft');
		$settings['posting']['post_status'] = in_array($post_status, ['draft', 'publish'], true) ? $post_status : 'draft';
		$settings['posting_daily'] = $this->sanitizePostingGroup($input['posting_daily'] ?? [], $settings['posting']);
		$settings['posting_free'] = $this->sanitizePostingGroup($input['posting_free'] ?? [], $settings['posting']);

		$daily_post_time = sanitize_text_field($input['posting']['daily_post_time'] ?? '06:00');
		$settings['posting']['daily_post_time'] = preg_match('/^\d{2}:\d{2}$/', $daily_post_time) ? $daily_post_time : '06:00';
		$settings['data_model']['default_market_target_key'] = sanitize_key($input['data_model']['default_market_target_key'] ?? 'tr-tr');
		$settings['data_model']['default_discount_store_key'] = sanitize_key($input['data_model']['default_discount_store_key'] ?? 'steam');
		$settings['data_model']['default_free_store_key'] = sanitize_key($input['data_model']['default_free_store_key'] ?? 'epic');
		$settings['data_model']['daily_repeat_window_days'] = max(0, absint($input['data_model']['daily_repeat_window_days'] ?? 7));
		$settings['data_model']['free_repeat_window_days'] = max(0, absint($input['data_model']['free_repeat_window_days'] ?? 7));
		$settings['source']['itad_session_token'] = sanitize_text_field($input['source']['itad_session_token'] ?? '');
		$settings['source']['itad_session_cookie'] = sanitize_text_field($input['source']['itad_session_cookie'] ?? '');
		$settings['source']['itad_visitor_cookie'] = sanitize_text_field($input['source']['itad_visitor_cookie'] ?? '');
		$settings['source']['itad_country_code'] = strtoupper(sanitize_text_field($input['source']['itad_country_code'] ?? 'TR'));
		$settings['source']['itad_currency_code'] = strtoupper(sanitize_text_field($input['source']['itad_currency_code'] ?? 'TRY'));
		$settings['source']['daily_payloads'] = $this->parseJsonPayloadList($input['source']['daily_payloads_json'] ?? '');
		$settings['source']['hourly_payloads'] = $this->parseJsonPayloadList($input['source']['hourly_payloads_json'] ?? '');

		return $settings;
	}

	public function renderSettingsPage(): void
	{
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Auto Games Discount Creator', 'auto-games-discount-creator'); ?></h1>
			<p><?php esc_html_e('settings.json remains the code default. Values saved here override those defaults for this site.', 'auto-games-discount-creator'); ?></p>
			<?php $this->renderPageStyles(); ?>
			<?php $this->renderTabbedLayoutScript(); ?>
			<?php $this->renderActionNotice(); ?>
			<?php $this->renderStatusCards(); ?>
			<?php $this->renderQuickActionsPanel(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields('agdc_settings_group');
				do_settings_sections('auto-games-discount-creator');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function addPluginActionLinks(array $actions): array
	{
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(admin_url('options-general.php?page=auto-games-discount-creator')),
			esc_html__('Settings', 'auto-games-discount-creator')
		);

		array_unshift($actions, $settings_link);

		return $actions;
	}

	public function handleTestFetch(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You are not allowed to perform this action.', 'auto-games-discount-creator'));
		}

		check_admin_referer('agdc_test_fetch');

		$type = sanitize_key($_REQUEST['agdc_test_fetch_type'] ?? 'hourly');
		if (!in_array($type, ['daily', 'hourly'], true)) {
			$type = 'hourly';
		}

		$notice_type = 'success';
		$notice_message = '';

		try {
			$query_results = (new Scraper($type))->getOffers();
			$database = new GameInformationDatabase();
			foreach ($query_results as $index => $query_result) {
				$query_results[$index] = $database->insertGameInformation($query_result);
			}

			$summary = $type === 'hourly'
				? $this->offerSelectionService->summarizeHourlySelection($query_results)
				: $this->offerSelectionService->summarizeDailySelection($query_results);

			$selected_results = $type === 'hourly'
				? $this->offerSelectionService->selectForHourly($query_results)
				: $this->offerSelectionService->selectForDaily($query_results);

			$this->runtimeStateRepository->markTestResult(
				'success',
				sprintf(
					'Fetched %d offer(s), selected %d for %s mode.',
					count($query_results),
					count($selected_results),
					$type
				),
				array_merge(
					[
						'type' => $type,
						'items' => count($selected_results),
					],
					$summary
				)
			);
			$notice_message = sprintf(
				'Test fetch completed for %s. %s',
				$type,
				$this->formatSelectionState($summary)
			);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markTestResult('error', $throwable->getMessage(), ['type' => $type]);
			$notice_type = 'error';
			$notice_message = sprintf('Test fetch failed for %s: %s', $type, $throwable->getMessage());
		}

		wp_safe_redirect($this->buildSettingsPageUrl($notice_type, $notice_message));
		exit;
	}

	public function handleRunTask(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You are not allowed to perform this action.', 'auto-games-discount-creator'));
		}

		check_admin_referer('agdc_run_task');

		$type = sanitize_key($_REQUEST['agdc_run_task_type'] ?? 'daily');
		$module = new ScheduleModule();

		$notice_type = 'success';
		$notice_message = '';

		try {
			if ($type === 'hourly') {
				$module->startHourlyPostTask();
			} else {
				$module->startDailyPostTask();
			}

			$run_state = $this->runtimeStateRepository->getAll()['last_run']['task:' . $type] ?? [];
			$notice_message = sprintf(
				'%s post run completed. %s',
				ucfirst($type),
				$this->formatSelectionState($run_state)
			);
		} catch (Throwable $throwable) {
			$this->runtimeStateRepository->markRunFailure($type, $throwable->getMessage());
			$notice_type = 'error';
			$notice_message = sprintf('%s post run failed: %s', ucfirst($type), $throwable->getMessage());
		}

		wp_safe_redirect($this->buildSettingsPageUrl($notice_type, $notice_message));
		exit;
	}

	public function handleCleanupDrafts(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You are not allowed to perform this action.', 'auto-games-discount-creator'));
		}

		check_admin_referer('agdc_cleanup_drafts');

		global $wpdb;

		$posts = get_posts(
			[
				'post_type' => 'agdc_roundup',
				'post_status' => 'draft',
				'numberposts' => -1,
				'meta_query' => [
					[
						'key' => '_agdc_content_kind',
						'compare' => 'EXISTS',
					],
				],
				'fields' => 'ids',
			]
		);

		$deleted = 0;
		foreach ($posts as $post_id) {
			$deleted_post = wp_delete_post((int) $post_id, true);
			if ($deleted_post) {
				$deleted++;
			}
		}

		if ($posts) {
			$placeholders = implode(', ', array_fill(0, count($posts), '%d'));
			$table = $wpdb->prefix . 'agdc_generated_posts';
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE wp_post_id IN ({$placeholders})",
					array_map('intval', $posts)
				)
			);
		}

		$this->runtimeStateRepository->markTestResult(
			'success',
			sprintf('Deleted %d AGDC draft post(s).', $deleted),
			[
				'type' => 'cleanup',
				'items' => $deleted,
			]
		);

		wp_safe_redirect(
			$this->buildSettingsPageUrl(
				'success',
				sprintf('Deleted %d AGDC draft post(s).', $deleted)
			)
		);
		exit;
	}

	public function renderGeneralSection(): void
	{
		echo '<span id="agdc_general_section-title"></span>';
		echo '<p>' . esc_html__('Basic runtime flags for the plugin.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderPostingSection(): void
	{
		echo '<span id="agdc_posting_section-title"></span>';
		echo '<p>' . esc_html__('Global WordPress posting defaults. Taxonomy is generated automatically per market/language, so this section only keeps the shared author, status and daily schedule controls.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderDailyPostingSection(): void
	{
		echo '<span id="agdc_daily_posting_section-title"></span>';
		echo '<p>' . esc_html__('Overrides used only for daily roundup posts. Localized category and tag terms are created automatically from the market target, so only author and status stay editable here.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderFreePostingSection(): void
	{
		echo '<span id="agdc_free_posting_section-title"></span>';
		echo '<p>' . esc_html__('Overrides used only for free-game posts. Localized category and tag terms are created automatically from the market target, so only author and status stay editable here.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderDataModelSection(): void
	{
		echo '<span id="agdc_data_model_section-title"></span>';
		echo '<p>' . esc_html__('This section controls market-first behaviour: default market, fallback stores and repeat windows that stop the same game from being reposted too often in the same market.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderSourceSection(): void
	{
		echo '<span id="agdc_source_section-title"></span>';
		echo '<p>' . esc_html__('This is the active scraper configuration. The plugin auto-bootstraps an anonymous ITAD session; token and cookie fields below are only manual overrides for debugging. Daily and hourly JSON payload blocks are the runtime source of truth.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderDebugSection(): void
	{
		echo '<span id="agdc_debug_section-title"></span>';
		echo '<p>' . esc_html__('Runtime status, latest errors and a safe manual fetch action for quick diagnostics.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderEnabledField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<label><input type="checkbox" name="%1$s[general][enabled]" value="1" %2$s> %3$s</label>',
			esc_attr(AGDC_SETTINGS_OPTION),
			checked(!empty($settings['general']['enabled']), true, false),
			esc_html__('Allow scheduled tasks and posting logic to run.', 'auto-games-discount-creator')
		);
	}

	public function renderDryRunField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<label><input type="checkbox" name="%1$s[general][dry_run]" value="1" %2$s> %3$s</label>',
			esc_attr(AGDC_SETTINGS_OPTION),
			checked(!empty($settings['general']['dry_run']), true, false),
			esc_html__('Keep runtime in safe mode while refactoring.', 'auto-games-discount-creator')
		);
	}

	public function renderAuthorField(): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected_author_id = (int) $settings['posting']['author_id'];
		$users = get_users(
			[
				'orderby' => 'display_name',
				'order' => 'ASC',
				'fields' => ['ID', 'display_name', 'user_login'],
			]
		);

		printf(
			'<select name="%1$s[posting][author_id]">',
			esc_attr(AGDC_SETTINGS_OPTION)
		);

		foreach ($users as $user) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $user->ID,
				selected($selected_author_id, (int) $user->ID, false),
				esc_html(sprintf('%s (@%s)', $user->display_name, $user->user_login))
			);
		}

		echo '</select>';
	}

	public function renderCategoryField(): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected_category_id = (int) $settings['posting']['category_id'];
		$categories = get_categories(
			[
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			]
		);

		printf(
			'<select name="%1$s[posting][category_id]">',
			esc_attr(AGDC_SETTINGS_OPTION)
		);

		printf(
			'<option value="0" %1$s>%2$s</option>',
			selected($selected_category_id, 0, false),
			esc_html__('No category', 'auto-games-discount-creator')
		);

		foreach ($categories as $category) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $category->term_id,
				selected($selected_category_id, (int) $category->term_id, false),
				esc_html($category->name)
			);
		}

		echo '</select>';
	}

	public function renderTagsField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<textarea class="large-text" rows="3" name="%1$s[posting][tags]">%2$s</textarea>',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_textarea((string) $settings['posting']['tags'])
		);
	}

	public function renderPostStatusField(): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected = (string) ($settings['posting']['post_status'] ?? 'draft');
		printf('<select name="%1$s[posting][post_status]">', esc_attr(AGDC_SETTINGS_OPTION));
		printf(
			'<option value="draft" %1$s>%2$s</option>',
			selected($selected, 'draft', false),
			esc_html__('Draft', 'auto-games-discount-creator')
		);
		printf(
			'<option value="publish" %1$s>%2$s</option>',
			selected($selected, 'publish', false),
			esc_html__('Publish', 'auto-games-discount-creator')
		);
		echo '</select> ';
		echo '<p class="description">' . esc_html__('Use draft while validating new market targets, slugs and content output.', 'auto-games-discount-creator') . '</p>';
	}

	public function renderDailyPostTimeField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="time" name="%1$s[posting][daily_post_time]" value="%2$s">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) $settings['posting']['daily_post_time'])
		);
	}

	public function renderDailyAuthorField(): void
	{
		$this->renderPostingUserSelect('posting_daily', 'author_id');
	}

	public function renderDailyCategoryField(): void
	{
		$this->renderPostingCategorySelect('posting_daily', 'category_id');
	}

	public function renderDailyTagsField(): void
	{
		$this->renderPostingTagsField('posting_daily');
	}

	public function renderDailyPostStatusField(): void
	{
		$this->renderPostingStatusField('posting_daily');
	}

	public function renderFreeAuthorField(): void
	{
		$this->renderPostingUserSelect('posting_free', 'author_id');
	}

	public function renderFreeCategoryField(): void
	{
		$this->renderPostingCategorySelect('posting_free', 'category_id');
	}

	public function renderFreeTagsField(): void
	{
		$this->renderPostingTagsField('posting_free');
	}

	public function renderFreePostStatusField(): void
	{
		$this->renderPostingStatusField('posting_free');
	}

	public function renderDefaultMarketTargetField(): void
	{
		$this->renderMarketTargetSelect('default_market_target_key');
	}

	public function renderDefaultDiscountStoreField(): void
	{
		$this->renderStoreSelect('default_discount_store_key');
	}

	public function renderDefaultFreeStoreField(): void
	{
		$this->renderStoreSelect('default_free_store_key');
	}

	public function renderDailyRepeatWindowField(): void
	{
		$this->renderRepeatWindowField('daily_repeat_window_days', __('Prevent the same game from appearing again in daily roundups for this many days.', 'auto-games-discount-creator'));
	}

	public function renderFreeRepeatWindowField(): void
	{
		$this->renderRepeatWindowField('free_repeat_window_days', __('Prevent the same free game from being reposted for this many days.', 'auto-games-discount-creator'));
	}

	public function renderItadSessionTokenField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="text" class="regular-text code" name="%1$s[source][itad_session_token]" value="%2$s" autocomplete="off">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) ($settings['source']['itad_session_token'] ?? ''))
		);
	}

	public function renderItadSessionCookieField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="text" class="regular-text code" name="%1$s[source][itad_session_cookie]" value="%2$s" autocomplete="off">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) ($settings['source']['itad_session_cookie'] ?? ''))
		);
	}

	public function renderItadVisitorCookieField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="text" class="regular-text code" name="%1$s[source][itad_visitor_cookie]" value="%2$s" autocomplete="off">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) ($settings['source']['itad_visitor_cookie'] ?? ''))
		);
	}

	public function renderItadCountryCodeField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="text" class="small-text" maxlength="8" name="%1$s[source][itad_country_code]" value="%2$s">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) ($settings['source']['itad_country_code'] ?? 'TR'))
		);
	}

	public function renderItadCurrencyCodeField(): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<input type="text" class="small-text" maxlength="8" name="%1$s[source][itad_currency_code]" value="%2$s">',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr((string) ($settings['source']['itad_currency_code'] ?? 'TRY'))
		);
	}

	public function renderDailyPayloadsField(): void
	{
		$this->renderSourcePayloadTextarea('daily_payloads_json', 'daily_payloads');
	}

	public function renderHourlyPayloadsJsonField(): void
	{
		$this->renderSourcePayloadTextarea('hourly_payloads_json', 'hourly_payloads');
	}

	public function renderLastRunField(): void
	{
		$state = $this->runtimeStateRepository->getAll();
		$last_run = $state['last_run'];

		$hourly = $last_run['task:hourly'] ?? [];
		$daily = $last_run['task:daily'] ?? [];

		echo '<div>';
		echo '<p><strong>' . esc_html__('Hourly', 'auto-games-discount-creator') . ':</strong> ' . esc_html($this->formatRunState($hourly)) . '</p>';
		echo '<p>' . esc_html($this->formatSelectionState($hourly)) . '</p>';
		echo '<p><strong>' . esc_html__('Daily', 'auto-games-discount-creator') . ':</strong> ' . esc_html($this->formatRunState($daily)) . '</p>';
		echo '<p>' . esc_html($this->formatSelectionState($daily)) . '</p>';
		echo '</div>';
	}

	public function renderLastErrorField(): void
	{
		$state = $this->runtimeStateRepository->getAll();
		$last_error = $state['last_error'];

		if (empty($last_error)) {
			echo '<p>' . esc_html__('No runtime errors recorded yet.', 'auto-games-discount-creator') . '</p>';
			return;
		}

		printf(
			'<p><strong>%1$s</strong> <code>%2$s</code></p><p><code>%3$s</code></p>',
			esc_html($last_error['at'] ?? ''),
			esc_html($last_error['task'] ?? 'unknown'),
			esc_html($last_error['message'] ?? '')
		);
	}

	public function renderNextRunField(): void
	{
		$hourly = wp_next_scheduled('startScheduleHourlyPost');
		$daily = wp_next_scheduled('startDailyPostTask');

		echo '<div>';
		echo '<p><strong>' . esc_html__('Hourly', 'auto-games-discount-creator') . ':</strong> ' . esc_html($this->formatTimestamp($hourly)) . '</p>';
		echo '<p><strong>' . esc_html__('Daily', 'auto-games-discount-creator') . ':</strong> ' . esc_html($this->formatTimestamp($daily)) . '</p>';
		echo '</div>';
	}

	public function renderTestFetchField(): void
	{
		$state = $this->runtimeStateRepository->getAll();
		$last_test = $state['last_test'];
		$hourly_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_test_fetch&agdc_test_fetch_type=hourly'),
			'agdc_test_fetch'
		);
		$daily_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_test_fetch&agdc_test_fetch_type=daily'),
			'agdc_test_fetch'
		);
		?>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url($hourly_url); ?>"><?php esc_html_e('Run Hourly Test', 'auto-games-discount-creator'); ?></a>
			<a class="button button-secondary" href="<?php echo esc_url($daily_url); ?>"><?php esc_html_e('Run Daily Test', 'auto-games-discount-creator'); ?></a>
		</p>
		<?php

		if (!empty($last_test)) {
			printf(
				'<p><strong>%1$s</strong> %2$s <code>%3$s</code></p>',
				esc_html($last_test['at'] ?? ''),
				esc_html(strtoupper((string) ($last_test['status'] ?? 'unknown'))),
				esc_html($last_test['message'] ?? '')
			);
			echo '<p>' . esc_html($this->formatSelectionState($last_test)) . '</p>';
		}
	}

	public function renderRunTasksField(): void
	{
		$hourly_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_run_task&agdc_run_task_type=hourly'),
			'agdc_run_task'
		);
		$daily_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_run_task&agdc_run_task_type=daily'),
			'agdc_run_task'
		);

		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url($hourly_url) . '">' . esc_html__('Run Hourly Post', 'auto-games-discount-creator') . '</a> ';
		echo '<a class="button button-primary" href="' . esc_url($daily_url) . '">' . esc_html__('Run Daily Post', 'auto-games-discount-creator') . '</a>';
		echo '</p>';
	}

	public function renderCleanupDraftsField(): void
	{
		$cleanup_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_cleanup_drafts'),
			'agdc_cleanup_drafts'
		);

		echo '<p>';
		echo '<a class="button button-secondary" href="' . esc_url($cleanup_url) . '">' . esc_html__('Delete AGDC Draft Posts', 'auto-games-discount-creator') . '</a>';
		echo '</p>';
	}

	private function renderMarketTargetSelect(string $settingKey): void
	{
		global $wpdb;

		$settings = $this->settingsRepository->getAll();
		$selected = (string) ($settings['data_model'][$settingKey] ?? '');
		$table = $wpdb->prefix . 'agdc_market_targets';
		$targets = $wpdb->get_results(
			"SELECT market_key, country_code, language_code, default_currency_code FROM {$table} ORDER BY market_key",
			ARRAY_A
		);

		printf('<select name="%1$s[data_model][%2$s]">', esc_attr(AGDC_SETTINGS_OPTION), esc_attr($settingKey));
		foreach ($targets as $target) {
			$label = sprintf(
				'%s (%s / %s / %s)',
				$target['market_key'],
				$target['country_code'],
				$target['language_code'],
				$target['default_currency_code']
			);
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr($target['market_key']),
				selected($selected, $target['market_key'], false),
				esc_html($label)
			);
		}
		echo '</select>';
	}

	private function renderSourcePayloadTextarea(string $fieldName, string $settingsKey): void
	{
		$settings = $this->settingsRepository->getAll();
		$payloads = $settings['source'][$settingsKey] ?? [];
		printf(
			'<textarea class="large-text code" rows="12" name="%1$s[source][%2$s]">%3$s</textarea>',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr($fieldName),
			esc_textarea(wp_json_encode($payloads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
		);
	}

	private function renderStoreSelect(string $settingKey): void
	{
		global $wpdb;

		$settings = $this->settingsRepository->getAll();
		$selected = (string) ($settings['data_model'][$settingKey] ?? '');
		$table = $wpdb->prefix . 'agdc_stores';
		$stores = $wpdb->get_results(
			"SELECT store_key, store_name FROM {$table} ORDER BY store_name",
			ARRAY_A
		);

		printf('<select name="%1$s[data_model][%2$s]">', esc_attr(AGDC_SETTINGS_OPTION), esc_attr($settingKey));
		foreach ($stores as $store) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr($store['store_key']),
				selected($selected, $store['store_key'], false),
				esc_html($store['store_name'])
			);
		}
		echo '</select>';
	}

	private function renderRepeatWindowField(string $settingKey, string $description): void
	{
		$settings = $this->settingsRepository->getAll();
		$value = (int) ($settings['data_model'][$settingKey] ?? 7);
		printf(
			'<input type="number" class="small-text" min="0" step="1" name="%1$s[data_model][%2$s]" value="%3$d"> <p class="description">%4$s</p>',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr($settingKey),
			$value,
			esc_html($description)
		);
	}

	private function sanitizePostingGroup(array $input, array $fallback): array
	{
		$author_id = max(1, absint($input['author_id'] ?? ($fallback['author_id'] ?? 1)));
		$category_id = max(0, absint($input['category_id'] ?? ($fallback['category_id'] ?? 0)));
		$tags = sanitize_text_field($input['tags'] ?? ($fallback['tags'] ?? ''));
		$post_status = sanitize_key($input['post_status'] ?? ($fallback['post_status'] ?? 'draft'));

		return [
			'author_id' => $author_id,
			'category_id' => $category_id,
			'tags' => $tags,
			'post_status' => in_array($post_status, ['draft', 'publish'], true) ? $post_status : 'draft',
		];
	}

	private function renderPostingUserSelect(string $groupKey, string $fieldKey): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected_author_id = (int) ($settings[$groupKey][$fieldKey] ?? 1);
		$users = get_users(
			[
				'orderby' => 'display_name',
				'order' => 'ASC',
				'fields' => ['ID', 'display_name', 'user_login'],
			]
		);

		printf('<select name="%1$s[%2$s][%3$s]">', esc_attr(AGDC_SETTINGS_OPTION), esc_attr($groupKey), esc_attr($fieldKey));
		foreach ($users as $user) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $user->ID,
				selected($selected_author_id, (int) $user->ID, false),
				esc_html(sprintf('%s (@%s)', $user->display_name, $user->user_login))
			);
		}
		echo '</select>';
	}

	private function renderPostingCategorySelect(string $groupKey, string $fieldKey): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected_category_id = (int) ($settings[$groupKey][$fieldKey] ?? 0);
		$categories = get_categories(
			[
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			]
		);

		printf('<select name="%1$s[%2$s][%3$s]">', esc_attr(AGDC_SETTINGS_OPTION), esc_attr($groupKey), esc_attr($fieldKey));
		printf('<option value="0" %1$s>%2$s</option>', selected($selected_category_id, 0, false), esc_html__('No category', 'auto-games-discount-creator'));
		foreach ($categories as $category) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $category->term_id,
				selected($selected_category_id, (int) $category->term_id, false),
				esc_html($category->name)
			);
		}
		echo '</select>';
	}

	private function renderPostingTagsField(string $groupKey): void
	{
		$settings = $this->settingsRepository->getAll();
		printf(
			'<textarea class="large-text" rows="3" name="%1$s[%2$s][tags]">%3$s</textarea>',
			esc_attr(AGDC_SETTINGS_OPTION),
			esc_attr($groupKey),
			esc_textarea((string) ($settings[$groupKey]['tags'] ?? ''))
		);
	}

	private function renderPostingStatusField(string $groupKey): void
	{
		$settings = $this->settingsRepository->getAll();
		$selected = (string) ($settings[$groupKey]['post_status'] ?? 'draft');
		printf('<select name="%1$s[%2$s][post_status]">', esc_attr(AGDC_SETTINGS_OPTION), esc_attr($groupKey));
		printf('<option value="draft" %1$s>%2$s</option>', selected($selected, 'draft', false), esc_html__('Draft', 'auto-games-discount-creator'));
		printf('<option value="publish" %1$s>%2$s</option>', selected($selected, 'publish', false), esc_html__('Publish', 'auto-games-discount-creator'));
		echo '</select>';
	}

	private function formatSelectionState(array $state): string
	{
		$parts = [];

		foreach (['found', 'eligible', 'already_posted', 'duplicates_removed', 'selected'] as $key) {
			if (array_key_exists($key, $state)) {
				$parts[] = sprintf('%s=%s', $key, (string) $state[$key]);
			}
		}

		return $parts ? implode(' | ', $parts) : __('No selection stats yet.', 'auto-games-discount-creator');
	}

	private function formatTimestamp($timestamp): string
	{
		if (empty($timestamp) || !is_numeric($timestamp)) {
			return __('Not scheduled', 'auto-games-discount-creator');
		}

		return wp_date('Y-m-d H:i:s', (int) $timestamp);
	}

	private function renderPageStyles(): void
	{
		?>
		<style>
			.agdc-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;max-width:1100px;margin:16px 0 24px}
			.agdc-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}
			.agdc-card h2,.agdc-card h3{margin:0 0 10px}
			.agdc-kpi-label{font-size:12px;color:#50575e;text-transform:uppercase}
			.agdc-kpi-value{font-size:20px;font-weight:600;margin-top:8px}
			.agdc-quick-actions{display:flex;flex-wrap:wrap;gap:8px;margin:0}
			.agdc-quick-actions .button{margin:0}
			.agdc-section-nav{display:flex;flex-wrap:wrap;gap:8px;margin:0}
			.agdc-section-nav a{text-decoration:none}
			.agdc-note{margin-top:8px;color:#50575e}
			.agdc-tab-panels{max-width:1100px}
			.agdc-tab-panel{display:none}
			.agdc-tab-panel.is-active{display:block}
			.agdc-tab-panel > h2:first-child{margin-top:0}
			.agdc-tab-panel .form-table:last-of-type{margin-bottom:0}
			.agdc-tabs{margin:8px 0 16px;max-width:1100px}
			.agdc-tabs .nav-tab{cursor:pointer}
			.form-table td p.description{max-width:720px}
			.form-table textarea.code{min-height:180px}
			.wrap .form-table{background:#fff;border:1px solid #dcdcde;border-radius:8px;margin:0 0 20px}
			.wrap .form-table th,.wrap .form-table td{padding:16px}
			.agdc-tab-panels h2{margin-top:28px;padding-top:12px;border-top:1px solid #dcdcde}
			.agdc-card h2{margin-top:0;padding-top:0;border-top:0}
			.agdc-inline-code{font-family:monospace}
			#agdc_general_section-title,
			#agdc_posting_section-title,
			#agdc_daily_posting_section-title,
			#agdc_free_posting_section-title,
			#agdc_data_model_section-title,
			#agdc_source_section-title,
			#agdc_debug_section-title{display:block;position:relative;top:-72px;visibility:hidden}
		</style>
		<?php
	}

	private function renderTabbedLayoutScript(): void
	{
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const form = document.querySelector('.wrap form[action="options.php"]');
				if (!form) {
					return;
				}

				const tabMap = {
					'General': 'general',
					'Posting': 'posting',
					'Daily Posting': 'posting',
					'Free Game Posting': 'posting',
					'Data Model': 'data',
					'Source': 'source',
					'Debug': 'debug'
				};

				const tabLabels = {
					'general': 'General',
					'posting': 'Posting',
					'data': 'Data Model',
					'source': 'Source',
					'debug': 'Debug'
				};

				const headings = Array.from(form.querySelectorAll(':scope > h2'));
				if (!headings.length) {
					return;
				}

				const panelsHost = document.createElement('div');
				panelsHost.className = 'agdc-tab-panels';
				const panels = {};

				Object.keys(tabLabels).forEach(function (key) {
					const panel = document.createElement('div');
					panel.className = 'agdc-tab-panel';
					panel.dataset.tab = key;
					panels[key] = panel;
					panelsHost.appendChild(panel);
				});

				headings.forEach(function (heading) {
					const title = heading.textContent.trim();
					const tabKey = tabMap[title] || 'general';
					let node = heading;
					const fragment = document.createDocumentFragment();

					while (node) {
						const current = node;
						node = node.nextElementSibling;
						fragment.appendChild(current);

						if (!node || (node.matches('h2') && current !== heading)) {
							break;
						}

						if (node && node.matches('h2')) {
							break;
						}
					}

					panels[tabKey].appendChild(fragment);
				});

				const submit = form.querySelector(':scope > p.submit');
				if (submit) {
					panelsHost.appendChild(submit);
				}

				form.appendChild(panelsHost);

				const nav = document.createElement('nav');
				nav.className = 'nav-tab-wrapper agdc-tabs';

				function activateTab(tabKey) {
					Object.values(panels).forEach(function (panel) {
						panel.classList.toggle('is-active', panel.dataset.tab === tabKey);
					});
					nav.querySelectorAll('.nav-tab').forEach(function (tab) {
						tab.classList.toggle('nav-tab-active', tab.dataset.tab === tabKey);
					});
					window.location.hash = 'agdc-tab-' + tabKey;
				}

				Object.entries(tabLabels).forEach(function (entry) {
					const key = entry[0];
					const label = entry[1];
					const tab = document.createElement('a');
					tab.href = '#agdc-tab-' + key;
					tab.className = 'nav-tab';
					tab.dataset.tab = key;
					tab.textContent = label;
					tab.addEventListener('click', function (event) {
						event.preventDefault();
						activateTab(key);
					});
					nav.appendChild(tab);
				});

				form.insertBefore(nav, panelsHost);

				document.querySelectorAll('[data-agdc-tab-target]').forEach(function (link) {
					link.addEventListener('click', function (event) {
						event.preventDefault();
						const target = link.getAttribute('data-agdc-tab-target');
						if (target && Object.prototype.hasOwnProperty.call(tabLabels, target)) {
							activateTab(target);
						}
					});
				});

				const initialHash = window.location.hash.replace('#agdc-tab-', '');
				const initialTab = Object.prototype.hasOwnProperty.call(tabLabels, initialHash) ? initialHash : 'general';
				activateTab(initialTab);
			});
		</script>
		<?php
	}

	private function renderActionNotice(): void
	{
		$notice_message = sanitize_text_field(wp_unslash($_GET['agdc_notice'] ?? ''));
		if ($notice_message === '') {
			return;
		}

		$notice_type = sanitize_key($_GET['agdc_notice_type'] ?? 'success');
		$css_class = $notice_type === 'error' ? 'notice notice-error' : 'notice notice-success is-dismissible';

		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_attr($css_class),
			esc_html($notice_message)
		);
	}

	private function renderQuickActionsPanel(): void
	{
		$hourly_run_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_run_task&agdc_run_task_type=hourly'),
			'agdc_run_task'
		);
		$daily_run_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_run_task&agdc_run_task_type=daily'),
			'agdc_run_task'
		);
		$hourly_test_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_test_fetch&agdc_test_fetch_type=hourly'),
			'agdc_test_fetch'
		);
		$daily_test_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_test_fetch&agdc_test_fetch_type=daily'),
			'agdc_test_fetch'
		);
		$cleanup_url = wp_nonce_url(
			admin_url('admin-post.php?action=agdc_cleanup_drafts'),
			'agdc_cleanup_drafts'
		);
		?>
		<div class="agdc-card-grid">
			<div class="agdc-card">
				<h2><?php esc_html_e('Quick Actions', 'auto-games-discount-creator'); ?></h2>
				<p class="agdc-note"><?php esc_html_e('Run the two automation flows, fetch-only diagnostics or cleanup without leaving this page.', 'auto-games-discount-creator'); ?></p>
				<p class="agdc-quick-actions">
					<a class="button button-primary" href="<?php echo esc_url($hourly_run_url); ?>"><?php esc_html_e('Run Hourly Post', 'auto-games-discount-creator'); ?></a>
					<a class="button button-primary" href="<?php echo esc_url($daily_run_url); ?>"><?php esc_html_e('Run Daily Post', 'auto-games-discount-creator'); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url($hourly_test_url); ?>"><?php esc_html_e('Run Hourly Test', 'auto-games-discount-creator'); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url($daily_test_url); ?>"><?php esc_html_e('Run Daily Test', 'auto-games-discount-creator'); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url($cleanup_url); ?>"><?php esc_html_e('Delete AGDC Draft Posts', 'auto-games-discount-creator'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	private function renderStatusCards(): void
	{
		$settings = $this->settingsRepository->getAll();
		$state = $this->runtimeStateRepository->getAll();
		$last_error = $state['last_error']['message'] ?? __('None', 'auto-games-discount-creator');
		$last_test = $state['last_test']['status'] ?? __('not-run', 'auto-games-discount-creator');
		$hourly_next = wp_next_scheduled('startScheduleHourlyPost');
		$daily_next = wp_next_scheduled('startDailyPostTask');
		?>
		<div class="agdc-card-grid">
			<div class="agdc-card">
				<div class="agdc-kpi-label"><?php esc_html_e('Automation', 'auto-games-discount-creator'); ?></div>
				<div class="agdc-kpi-value"><?php echo !empty($settings['general']['enabled']) ? esc_html__('Enabled', 'auto-games-discount-creator') : esc_html__('Disabled', 'auto-games-discount-creator'); ?></div>
			</div>
			<div class="agdc-card">
				<div class="agdc-kpi-label"><?php esc_html_e('Dry Run', 'auto-games-discount-creator'); ?></div>
				<div class="agdc-kpi-value"><?php echo !empty($settings['general']['dry_run']) ? esc_html__('On', 'auto-games-discount-creator') : esc_html__('Off', 'auto-games-discount-creator'); ?></div>
			</div>
			<div class="agdc-card">
				<div class="agdc-kpi-label"><?php esc_html_e('Last Test', 'auto-games-discount-creator'); ?></div>
				<div class="agdc-kpi-value"><?php echo esc_html(is_string($last_test) ? strtoupper($last_test) : 'NOT-RUN'); ?></div>
				<div class="agdc-note"><?php echo esc_html(wp_html_excerpt((string) $last_error, 120, '...')); ?></div>
			</div>
			<div class="agdc-card">
				<div class="agdc-kpi-label"><?php esc_html_e('Next Hourly Run', 'auto-games-discount-creator'); ?></div>
				<div class="agdc-kpi-value" style="font-size:16px;"><?php echo esc_html($this->formatTimestamp($hourly_next)); ?></div>
			</div>
			<div class="agdc-card">
				<div class="agdc-kpi-label"><?php esc_html_e('Next Daily Run', 'auto-games-discount-creator'); ?></div>
				<div class="agdc-kpi-value" style="font-size:16px;"><?php echo esc_html($this->formatTimestamp($daily_next)); ?></div>
			</div>
		</div>
		<?php
	}

	private function buildSettingsPageUrl(string $noticeType, string $noticeMessage): string
	{
		return add_query_arg(
			[
				'page' => 'auto-games-discount-creator',
				'agdc_notice_type' => $noticeType,
				'agdc_notice' => $noticeMessage,
			],
			admin_url('options-general.php')
		);
	}

	private function formatRunState(array $run): string
	{
		if (empty($run)) {
			return __('Never run', 'auto-games-discount-creator');
		}

		$parts = [];
		if (!empty($run['status'])) {
			$parts[] = 'status=' . $run['status'];
		}
		if (isset($run['items'])) {
			$parts[] = 'items=' . (int) $run['items'];
		}
		if (!empty($run['finished_at'])) {
			$parts[] = 'at=' . $run['finished_at'];
		}
		if (!empty($run['note'])) {
			$parts[] = 'note=' . $run['note'];
		}

		return implode(' | ', $parts);
	}

	private function parseJsonPayloadList(string $json): array
	{
		$json = trim($json);
		if ($json === '') {
			return [];
		}

		$decoded = json_decode($json, true);
		return is_array($decoded) ? array_values($decoded) : [];
	}
}
