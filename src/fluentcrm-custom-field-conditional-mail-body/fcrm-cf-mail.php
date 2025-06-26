<?php
/**
 * Plugin Name: FluentCRM Custom Field Conditional Content
 * Description: Extends FluentCRM to support conditional content based on custom field values in email templates
 * Version: 1.0.0
 * Author: Custom Development
 * Requires at least: 5.0
 * Tested up to: 6.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FluentCRMCustomFieldConditional
{
    public function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        // Add shortcode for custom field conditional content
        add_shortcode('fc_custom_field_conditional', [$this, 'handleCustomFieldConditional']);
        
        // Add filter to parse custom field conditionals in email content
        add_filter('fluent_crm/parse_campaign_email_text', [$this, 'parseCustomFieldConditionals'], 10, 2);
        
        // Add filter to parse custom field conditionals in visual builder
        add_filter('fluent_crm/email-design-template-visual_builder', [$this, 'parseCustomFieldConditionals'], 10, 4);
        
        // Add custom block type for Gutenberg editor
        add_action('init', [$this, 'registerCustomFieldConditionalBlock']);
    }

    /**
     * Register custom Gutenberg block for custom field conditional content
     */
    public function registerCustomFieldConditionalBlock()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type('fluent-crm/custom-field-conditional', [
            'editor_script' => 'fluentcrm-blocks-block-editor',
            'editor_style' => 'fluentcrm-blocks-block-editor',
            'attributes' => [
                'field_slug' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'operator' => [
                    'type' => 'string',
                    'default' => 'equals'
                ],
                'value' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'show_if_true' => [
                    'type' => 'string',
                    'default' => ''
                ],
                'show_if_false' => [
                    'type' => 'string',
                    'default' => ''
                ]
            ],
            'render_callback' => [$this, 'renderCustomFieldConditionalBlock']
        ]);
    }

    /**
     * Render custom field conditional block
     */
    public function renderCustomFieldConditionalBlock($attributes, $content)
    {
        $fieldSlug = $attributes['field_slug'] ?? '';
        $operator = $attributes['operator'] ?? 'equals';
        $value = $attributes['value'] ?? '';
        $showIfTrue = $attributes['show_if_true'] ?? '';
        $showIfFalse = $attributes['show_if_false'] ?? '';

        if (!$fieldSlug) {
            return $showIfFalse;
        }

        $subscriber = fluentcrm_get_current_contact();
        if (!$subscriber) {
            return $showIfFalse;
        }

        $customFields = $subscriber->custom_fields();
        $fieldValue = isset($customFields[$fieldSlug]) ? $customFields[$fieldSlug] : '';

        if ($this->evaluateCondition($fieldValue, $operator, $value)) {
            return $this->parseContent($showIfTrue, $subscriber);
        } else {
            return $this->parseContent($showIfFalse, $subscriber);
        }
    }

    /**
     * Handle custom field conditional shortcode
     */
    public function handleCustomFieldConditional($atts, $content = '')
    {
        $atts = shortcode_atts([
            'field' => '',
            'operator' => 'equals',
            'value' => '',
            'show_if_true' => '',
            'show_if_false' => ''
        ], $atts, 'fc_custom_field_conditional');

        $fieldSlug = $atts['field'];
        $operator = $atts['operator'];
        $value = $atts['value'];
        $showIfTrue = $atts['show_if_true'];
        $showIfFalse = $atts['show_if_false'];

        if (!$fieldSlug) {
            return $showIfFalse;
        }

        $subscriber = fluentcrm_get_current_contact();
        if (!$subscriber) {
            return $showIfFalse;
        }

        $customFields = $subscriber->custom_fields();
        $fieldValue = isset($customFields[$fieldSlug]) ? $customFields[$fieldSlug] : '';

        if ($this->evaluateCondition($fieldValue, $operator, $value)) {
            return $this->parseContent($showIfTrue, $subscriber);
        } else {
            return $this->parseContent($showIfFalse, $subscriber);
        }
    }

    /**
     * Parse custom field conditionals in email content
     */
    public function parseCustomFieldConditionals($content, $subscriber)
    {
        if (!$subscriber || !is_object($subscriber)) {
            return $content;
        }

        // Parse custom field conditionals using regex
        $pattern = '/\[fc_custom_field_conditional\s+field="([^"]+)"\s+operator="([^"]+)"\s+value="([^"]*)"\s+show_if_true="([^"]*)"\s+show_if_false="([^"]*)"\]/i';
        
        return preg_replace_callback($pattern, function($matches) use ($subscriber) {
            $fieldSlug = $matches[1];
            $operator = $matches[2];
            $value = $matches[3];
            $showIfTrue = $matches[4];
            $showIfFalse = $matches[5];

            $customFields = $subscriber->custom_fields();
            $fieldValue = isset($customFields[$fieldSlug]) ? $customFields[$fieldSlug] : '';

            if ($this->evaluateCondition($fieldValue, $operator, $value)) {
                return $this->parseContent($showIfTrue, $subscriber);
            } else {
                return $this->parseContent($showIfFalse, $subscriber);
            }
        }, $content);
    }

    /**
     * Evaluate condition based on operator
     */
    public function evaluateCondition($fieldValue, $operator, $value)
    {
        switch ($operator) {
            case 'equals':
            case '=':
                return $fieldValue == $value;
            
            case 'not_equals':
            case '!=':
                return $fieldValue != $value;
            
            case 'contains':
                return strpos($fieldValue, $value) !== false;
            
            case 'not_contains':
                return strpos($fieldValue, $value) === false;
            
            case 'starts_with':
                return strpos($fieldValue, $value) === 0;
            
            case 'ends_with':
                return substr($fieldValue, -strlen($value)) === $value;
            
            case 'greater_than':
            case '>':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue > $value;
            
            case 'less_than':
            case '<':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue < $value;
            
            case 'greater_than_or_equal':
            case '>=':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue >= $value;
            
            case 'less_than_or_equal':
            case '<=':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue <= $value;
            
            case 'empty':
                return empty($fieldValue);
            
            case 'not_empty':
                return !empty($fieldValue);
            
            default:
                return false;
        }
    }

    /**
     * Parse content with FluentCRM shortcodes
     */
    private function parseContent($content, $subscriber)
    {
        if (!$content) {
            return '';
        }

        // Parse FluentCRM shortcodes
        if (class_exists('\FluentCrm\App\Services\Libs\Parser\Parser')) {
            return \FluentCrm\App\Services\Libs\Parser\Parser::parse($content, $subscriber);
        }

        return $content;
    }

    /**
     * Get available custom fields for the admin interface
     */
    public static function getCustomFields()
    {
        $customFields = fluentcrm_get_option('contact_custom_fields', []);
        $fields = [];
        
        foreach ($customFields as $field) {
            $fields[] = [
                'slug' => $field['slug'],
                'label' => $field['label'],
                'type' => $field['type']
            ];
        }
        
        return $fields;
    }

    /**
     * Get available operators
     */
    public static function getOperators()
    {
        return [
            'equals' => 'Equals (=)',
            'not_equals' => 'Not Equals (!=)',
            'contains' => 'Contains',
            'not_contains' => 'Not Contains',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'greater_than' => 'Greater Than (>)',
            'less_than' => 'Less Than (<)',
            'greater_than_or_equal' => 'Greater Than or Equal (>=)',
            'less_than_or_equal' => 'Less Than or Equal (<=)',
            'empty' => 'Is Empty',
            'not_empty' => 'Is Not Empty'
        ];
    }
}

// Initialize the plugin
new FluentCRMCustomFieldConditional();

/**
 * Helper function to get custom field value
 */
function fluentcrm_get_custom_field_value($field_slug, $subscriber = null)
{
    if (!$subscriber) {
        $subscriber = fluentcrm_get_current_contact();
    }
    
    if (!$subscriber) {
        return '';
    }
    
    $customFields = $subscriber->custom_fields();
    return isset($customFields[$field_slug]) ? $customFields[$field_slug] : '';
}

/**
 * Helper function to check custom field condition
 */
function fluentcrm_check_custom_field_condition($field_slug, $operator, $value, $subscriber = null)
{
    if (!$subscriber) {
        $subscriber = fluentcrm_get_current_contact();
    }
    
    if (!$subscriber) {
        return false;
    }
    
    $customFields = $subscriber->custom_fields();
    $fieldValue = isset($customFields[$field_slug]) ? $customFields[$field_slug] : '';
    
    $conditional = new FluentCRMCustomFieldConditional();
    return $conditional->evaluateCondition($fieldValue, $operator, $value);
}