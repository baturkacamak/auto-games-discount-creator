<?php

namespace Rambouillet;

if (!class_exists('Rambouillet\Settings')) {
    class Settings extends \FW_Settings_Form
    {
        protected function _init()
        {
            add_action('admin_menu', [$this, 'addSettingsPage']);
        }

        public function addSettingsPage()
        {
            add_submenu_page(
                'options-general.php',
                esc_html__('Rambouillet', 'rambouillet'),
                esc_html__('Rambouillet', 'rambouillet'),
                'manage_options',
                'rambouillet-settings',
                [$this, 'render']
            );
        }

        public function get_options()
        {
            return [
                'box_settings' => [
                    'type' => 'box',
                    'title' => esc_html__('Rambouillet Plugin Settings', 'mariselle'),
                    'options' => [
                        'base_url' => ['type' => 'text'],
                        'lazy_deals' => ['type' => 'text'],
                        'queries' => [
                            'type' => 'addable-box',
                            'template' => '{{- query_key }}', // box title
                            'box-options' => [
                                'query_key' => [
                                    'type' => 'text',
                                    'attr' => ['autocomplete' => 'off'],
                                ],
                                'query_value' => [
                                    'type' => 'text',
                                    'attr' => ['autocomplete' => 'off'],
                                ],
                                'query_type' => [
                                    'value' => 'daily',
                                    'type' => 'select',
                                    'choices' => [
                                        'daily' => 'Daily',
                                        'hourly' => 'Hourly'
                                    ]
                                ],
                            ],
                        ],
                        'tags' => ['type' => 'textarea'],
                    ],
                ],
            ];
        }

        /**
         * @inheritDoc
         */
        public function get_values($key = null, $default_value = [])
        {
            return \FW_WP_Option::get(
                'rambouillet:rambouillet',
                $key,
                $default_value
            );
        }

        /**
         * @inheritDoc
         */
        public function set_values($values)
        {
            \FW_WP_Option::set('rambouillet:rambouillet', null, $values);

            return $this;
        }
    }
}
