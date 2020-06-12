<?php

namespace Rambouillet\Unyson;

use FW_Settings_Form;

if (!class_exists('Rambouillet\Unyson\PluginSettings')) {
    /**
     * Used in fw()->backend
     *
     * @internal
     */
    class PluginSettings extends FW_Settings_Form
    {
        /**
         * @return array
         */
        public function get_options()
        {
            return [
                'box_settings' => [
                    'type' => 'box',
                    'title' => esc_html__('Rambouillet Plugin Settings', 'mariselle'),
                    'options' => [
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
         * @param array|callable $values
         *
         * @return $this|FW_Settings_Form
         */
        public function set_values($values)
        {
            \FW_WP_Option::set('rambouillet:rambouillet', null, $values);

            return $this;
        }

        /**
         * @param null $key
         *
         * @return array|mixed|null
         */
        public function get_values($key = null)
        {
            return \FW_WP_Option::get(
                'rambouillet:rambouillet',
                $key,
                []
            );
        }

        /**
         * @internal
         */
        public function _action_admin_menu()
        {
            $data = [
                'capability' => 'manage_options',
                'slug' => fw()->backend->_get_settings_page_slug(),
                'content_callback' => [$this, 'render'],
            ];

            if (!current_user_can($data['capability'])) {
                return;
            }

            if (fw()->theme->get_config('disable_theme_settings_page', false)) {
                return;
            }

            /**
             * Collect $hookname that contains $data['slug'] before the action
             * and skip them in verification after action
             */ {
            global $_registered_pages;

            $found_hooknames = [];

            if (!empty($_registered_pages)) {
                foreach ($_registered_pages as $hookname => $b) {
                    if (strpos($hookname, $data['slug']) !== false) {
                        $found_hooknames[$hookname] = true;
                    }
                }
            }
        }

            /**
             * Check if settings menu was added in the action above
             */ {
            $menu_exists = false;

            if (!empty($_registered_pages)) {
                foreach ($_registered_pages as $hookname => $b) {
                    if (isset($found_hooknames[$hookname])) {
                        continue;
                    }

                    if (strpos($hookname, $data['slug']) !== false) {
                        $menu_exists = true;
                        break;
                    }
                }
            }
        }

            if ($menu_exists) {
                return;
            }

            add_theme_page(
                __('Rambouillet Settings', 'fw'),
                __('Rambouillet Settings', 'fw'),
                $data['capability'],
                'rambouillet-settings',
                $data['content_callback']
            );
        }


        /**
         *
         */
        protected function _init()
        {
            add_action('admin_menu', [$this, '_action_admin_menu']);
        }
    }
}
