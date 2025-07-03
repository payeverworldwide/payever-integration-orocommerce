<?php
// phpcs:ignoreFile

namespace Payever\Bundle\PaymentBundle\Attribute;

use Attribute;

if (class_exists('\Oro\Bundle\LayoutBundle\Attribute\Layout')) {
    /**
     * The Layout class handles the Layout attribute parts.
     * @uses OroCommerce 6
     */
    #[Attribute(Attribute::TARGET_METHOD)]
    class Layout extends \Oro\Bundle\LayoutBundle\Attribute\Layout
    {
    }
} else {
    /**
     * The Layout class handles the Layout attribute parts.
     * @uses OroCommerce 5
     */
    #[Attribute(Attribute::TARGET_METHOD)]
    class Layout extends \Oro\Bundle\LayoutBundle\Annotation\Layout
    {
        /**
         * @param string $action The controller action type.
         * @param array|string $blockThemes The block theme(s).
         * @param string $theme The layout theme name.
         * @param array|null $vars The layout context variables.
         */
        public function __construct(
            private string $action = '',
            private array|string $blockThemes = '',
            private string $theme = '',
            private ?array $vars = null,
        ) {
            parent::__construct(
                [
                    'action' => $action,
                    'blockThemes' => $blockThemes,
                    'theme' => $theme,
                    'vars' => $vars
                ]
            );
        }
    }
}
