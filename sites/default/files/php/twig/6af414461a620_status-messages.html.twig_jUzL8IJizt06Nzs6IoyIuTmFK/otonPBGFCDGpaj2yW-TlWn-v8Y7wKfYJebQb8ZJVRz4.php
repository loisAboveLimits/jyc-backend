<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\MacroNamespace;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* core/themes/olivero/templates/misc/status-messages.html.twig */
class __TwigTemplate_64d482ce0d1e38f68ad4bffc6dd114e4 extends Template
{
    private Source $source;
    /**
     * @var array<string, MacroNamespace>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        $this->sandbox = $env->getExtension(SandboxExtension::class)->getChecker();
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 22
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("olivero/messages"), "html", null, true);
        yield "

<div data-drupal-messages class=\"messages-list\">
  <div class=\"messages__wrapper layout-container\">
    ";
        // line 26
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["message_list"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["type"] => $context["messages"]) {
            // line 27
            yield "      ";
            // line 28
            $context["classes"] = ["messages-list__item", "messages", ("messages--" .             // line 31
$context["type"])];
            // line 34
            yield "
      <div";
            // line 35
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [            // line 36
($context["classes"] ?? null)], "method", false, false, true, 35), "setAttribute", ["data-drupal-selector", "messages"], "method", false, false, true, 36), "setAttribute", ["role", "contentinfo"], "method", false, false, true, 37), "setAttribute", ["aria-label", (($_v0 =             // line 39
($context["status_headings"] ?? null)) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0[(($_v1 = $context["type"]) instanceof \Stringable && (is_array($_v0) || $_v0 instanceof \ArrayObject || $_v0 instanceof \ArrayIterator) ? (string) $_v1 : $_v1)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["status_headings"] ?? null), $context["type"], [], "array", false, false, true, 39))], "method", false, false, true, 38), "html", null, true);
            // line 40
            yield ">
        <div class=\"messages__container\" data-drupal-selector=\"messages-container\"";
            // line 41
            if (($context["type"] == "error")) {
                yield " role=\"alert\"";
            }
            yield ">
          ";
            // line 42
            if ((($tmp = (($_v2 = ($context["status_headings"] ?? null)) && is_array($_v2) || $_v2 instanceof ArrayAccess && in_array($_v2::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v2[(($_v3 = $context["type"]) instanceof \Stringable && (is_array($_v2) || $_v2 instanceof \ArrayObject || $_v2 instanceof \ArrayIterator) ? (string) $_v3 : $_v3)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["status_headings"] ?? null), $context["type"], [], "array", false, false, true, 42))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 43
                yield "            <div class=\"messages__header\">
            <h2 class=\"visually-hidden\">";
                // line 44
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($_v4 = ($context["status_headings"] ?? null)) && is_array($_v4) || $_v4 instanceof ArrayAccess && in_array($_v4::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v4[(($_v5 = $context["type"]) instanceof \Stringable && (is_array($_v4) || $_v4 instanceof \ArrayObject || $_v4 instanceof \ArrayIterator) ? (string) $_v5 : $_v5)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["status_headings"] ?? null), $context["type"], [], "array", false, false, true, 44)), "html", null, true);
                yield "</h2>
              <div class=\"messages__icon\">
                ";
                // line 46
                if (($context["type"] == "error")) {
                    // line 47
                    yield "                  ";
                    yield from $this->load("@olivero/../images/error.svg", 47)->unwrap()->yield($context);
                    // line 48
                    yield "                ";
                } elseif (($context["type"] == "warning")) {
                    // line 49
                    yield "                  ";
                    yield from $this->load("@olivero/../images/warning.svg", 49)->unwrap()->yield($context);
                    // line 50
                    yield "                ";
                } elseif (($context["type"] == "status")) {
                    // line 51
                    yield "                  ";
                    yield from $this->load("@olivero/../images/status.svg", 51)->unwrap()->yield($context);
                    // line 52
                    yield "                ";
                } elseif (($context["type"] == "info")) {
                    // line 53
                    yield "                  ";
                    yield from $this->load("@olivero/../images/info.svg", 53)->unwrap()->yield($context);
                    // line 54
                    yield "                ";
                }
                // line 55
                yield "              </div>
            </div>
          ";
            }
            // line 58
            yield "          <div class=\"messages__content\">
            ";
            // line 59
            if ((Twig\Extension\CoreExtension::length($this->env->getCharset(), $context["messages"]) > 1)) {
                // line 60
                yield "              <ul class=\"messages__list\">
                ";
                // line 61
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable($context["messages"]);
                foreach ($context['_seq'] as $context["_key"] => $context["message"]) {
                    // line 62
                    yield "                  <li class=\"messages__item\">";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["message"], "html", null, true);
                    yield "</li>
                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['message'], $context['_parent']);
                $context = array_intersect_key($context, $_parent);
                $context += $_parent;
                // line 64
                yield "              </ul>
            ";
            } else {
                // line 66
                yield "              ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::first($this->env->getCharset(), $context["messages"]), "html", null, true);
                yield "
            ";
            }
            // line 68
            yield "          </div>
        </div>
      </div>
      ";
            // line 72
            yield "      ";
            $context["attributes"] = CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "removeClass", [($context["classes"] ?? null)], "method", false, false, true, 72);
            // line 73
            yield "    ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['type'], $context['messages'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 74
        yield "  </div>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["message_list", "attributes", "status_headings"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/olivero/templates/misc/status-messages.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDefaultEscapeStrategy(): string|false
    {
        return "html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  186 => 74,  171 => 73,  168 => 72,  163 => 68,  157 => 66,  153 => 64,  143 => 62,  139 => 61,  136 => 60,  134 => 59,  131 => 58,  126 => 55,  123 => 54,  120 => 53,  117 => 52,  114 => 51,  111 => 50,  108 => 49,  105 => 48,  102 => 47,  100 => 46,  95 => 44,  92 => 43,  90 => 42,  84 => 41,  81 => 40,  79 => 39,  78 => 36,  77 => 35,  74 => 34,  72 => 31,  71 => 28,  69 => 27,  52 => 26,  45 => 22,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/olivero/templates/misc/status-messages.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\core\\themes\\olivero\\templates\\misc\\status-messages.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["for" => 26, "set" => 28, "if" => 41, "include" => 47];
        static $filters = ["escape" => 22, "length" => 59, "first" => 66];
        static $functions = ["attach_library" => 22];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "for", 1 => "set", 2 => "if", 3 => "include"],
                [0 => "escape", 1 => "length", 2 => "first"],
                [0 => "attach_library"],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            } elseif ($e instanceof SecurityNotAllowedTestError && isset($tests[$e->getTestName()])) {
                $e->setTemplateLine($tests[$e->getTestName()]);
            }

            throw $e;
        }

    }
}
