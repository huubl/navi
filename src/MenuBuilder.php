<?php

namespace Log1x\Navi;

class MenuBuilder
{
    /**
     * The current menu.
     */
    protected array $menu = [];

    /**
     * The attributes map.
     */
    protected array $attributes = [
        'active' => 'current',
        'activeAncestor' => 'current_item_ancestor',
        'activeParent' => 'current_item_parent',
        'classes' => 'classes',
        'dbId' => 'db_id',
        'description' => 'description',
        'id' => 'ID',
        'label' => 'title',
        'object' => 'object',
        'objectId' => 'object_id',
        'order' => 'menu_order',
        'parent' => 'menu_item_parent',
        'slug' => 'post_name',
        'target' => 'target',
        'title' => 'attr_title',
        'type' => 'type',
        'url' => 'url',
        'xfn' => 'xfn',
    ];

    /**
     * The classes to remove from menu items.
     */
    protected array $withoutClasses = [];

    /**
     * Make a new Menu Builder instance.
     */
    public static function make(): self
    {
        return new static;
    }

    /**
     * Build the navigation menu.
     */
    public function build(array $menu = []): array
    {
        $this->menu = $this->filter($menu);

        if (! $this->menu) {
            return [];
        }

        $this->menu = array_combine(
            array_column($this->menu, 'ID'),
            $this->menu
        );

        return $this->handle(
            $this->map($this->menu)
        );
    }

    /**
     * Filter the menu items.
     */
    protected function filter(array $menu = []): array
    {
        $menu = array_filter($menu, fn ($item) => is_a($item, 'WP_Post') || is_a($item, 'WPML_LS_Menu_Item'));

        if (! $menu) {
            return [];
        }

        _wp_menu_item_classes_by_context($menu);

        return array_map(function ($item) {
            $classes = array_filter($item->classes, function ($class) {
                foreach ($this->withoutClasses as $value) {
                    if (str_starts_with($class, $value)) {
                        return false;
                    }
                }

                return true;
            });

            $item->classes = is_array($classes) ? implode(' ', $classes) : $classes;

            foreach ($item as $key => $value) {
                if (! $value) {
                    $item->{$key} = false;
                }
            }

            return $item;
        }, $menu);
    }

    /**
     * Map the menu items into an object.
     */
    protected function map(array $menu = []): array
    {
        return array_map(function ($item) {
            $result = [];

            foreach ($this->attributes as $key => $value) {
                $result[$key] = $item->{$value};
            }

            $result['parentObjectId'] = ! empty($result['parent']) && ! empty($this->menu[$result['parent']])
                ? $this->menu[$result['parent']]->object_id
                : false;

            return (object) $result;
        }, $menu);
    }

    /**
     * Handle the menu item hierarchy.
     */
    protected function handle(array $items, string|int $parent = 0): array
    {
        $menu = [];

        foreach ($items as $item) {
            if ($item->parent != $parent) {
                continue;
            }

            $item->children = $this->handle($items, $item->id);

            $menu[$item->id] = $item;
        }

        return $menu;
    }

    /**
     * Remove classes from menu items.
     */
    public function withoutClasses(array $classes = []): self
    {
        $this->withoutClasses = $classes;

        return $this;
    }
}
