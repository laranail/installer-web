<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Installer\Web\Support;

use Closure;
use Illuminate\Support\Facades\View;

/**
 * Runtime registry for web-UI decoration — lets a consumer present/replace any step's
 * UI (view, Livewire component, layout, slot content, custom field types) without
 * editing this package. Populated via the {@see InstallerUi}
 * facade from a consumer provider's boot(); read by the controller, layout and views.
 */
final class WebUiRegistry
{
    /** @var array<string, string> step key => Blade view */
    private array $views = [];

    /** @var array<string, class-string> step key => Livewire component class */
    private array $components = [];

    /** @var array<string, list<string|Closure>> slot name => content (view name, raw html, or closure) */
    private array $sections = [];

    /** @var array<string, string> field type => Blade view */
    private array $fieldTypes = [];

    private ?string $layout = null;

    public function setView(string $step, string $view): void
    {
        $this->views[$step] = $view;
    }

    public function view(string $step): ?string
    {
        return $this->views[$step] ?? null;
    }

    /** @param  class-string  $component */
    public function setComponent(string $step, string $component): void
    {
        $this->components[$step] = $component;
    }

    /** @return class-string|null */
    public function component(string $step): ?string
    {
        return $this->components[$step] ?? null;
    }

    public function setLayout(string $view): void
    {
        $this->layout = $view;
    }

    public function layout(): ?string
    {
        return $this->layout;
    }

    public function addSection(string $name, string|Closure $content): void
    {
        $this->sections[$name][] = $content;
    }

    /**
     * Render every piece of content registered for a slot: a Blade view name is
     * rendered, a closure is invoked, anything else is treated as raw HTML.
     */
    public function renderSection(string $name): string
    {
        $out = '';

        foreach ($this->sections[$name] ?? [] as $content) {
            if ($content instanceof Closure) {
                $out .= (string) $content();

                continue;
            }

            $out .= View::exists($content) ? View::make($content)->render() : $content;
        }

        return $out;
    }

    public function setFieldType(string $type, string $view): void
    {
        $this->fieldTypes[$type] = $view;
    }

    public function fieldType(string $type): ?string
    {
        return $this->fieldTypes[$type] ?? null;
    }
}
