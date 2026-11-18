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

/* core/themes/claro/templates/media-library/views-view--media-library.html.twig */
class __TwigTemplate_828a8be0daa0c5e4ca2043c01cefa3df extends Template
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
        // line 36
        $context["classes"] = ["view", ("view-" . \Drupal\Component\Utility\Html::getClass(        // line 38
($context["id"] ?? null))), ("view-id-" .         // line 39
($context["id"] ?? null)), ("view-display-id-" .         // line 40
($context["display_id"] ?? null)), (((($tmp =         // line 41
($context["dom_id"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("js-view-dom-id-" . ($context["dom_id"] ?? null))) : (""))];
        // line 44
        yield "<div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 44), "html", null, true);
        yield ">
  ";
        // line 45
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
        yield "
  ";
        // line 46
        if ((($tmp = ($context["title"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 47
            yield "    ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
            yield "
  ";
        }
        // line 49
        yield "  ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
        yield "
  ";
        // line 50
        if ((($tmp = ($context["exposed"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 51
            yield "    <div class=\"view-filters\">
      ";
            // line 52
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["exposed"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 55
        yield "  ";
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 56
            yield "    <div class=\"view-header\">
      ";
            // line 57
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["header"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 60
        yield "  ";
        if ((($tmp = ($context["attachment_before"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 61
            yield "    <div class=\"attachment attachment-before\">
      ";
            // line 62
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attachment_before"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 65
        yield "
  ";
        // line 66
        if ((($tmp = ($context["rows"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 67
            yield "    <div class=\"view-content\">
      ";
            // line 68
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["rows"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        } elseif ((($tmp =         // line 70
($context["empty"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 71
            yield "    <div class=\"view-empty\">
      ";
            // line 72
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["empty"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 75
        yield "
  ";
        // line 76
        if ((($tmp = ($context["pager"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 77
            yield "    ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["pager"] ?? null), "html", null, true);
            yield "
  ";
        }
        // line 79
        yield "  ";
        if ((($tmp = ($context["attachment_after"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 80
            yield "    <div class=\"attachment attachment-after\">
      ";
            // line 81
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attachment_after"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 84
        yield "  ";
        if ((($tmp = ($context["more"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 85
            yield "    ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["more"] ?? null), "html", null, true);
            yield "
  ";
        }
        // line 87
        yield "  ";
        if ((($tmp = ($context["footer"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 88
            yield "    <div class=\"view-footer\">
      ";
            // line 89
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["footer"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 92
        yield "  ";
        if ((($tmp = ($context["feed_icons"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 93
            yield "    <div class=\"feed-icons\">
      ";
            // line 94
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["feed_icons"] ?? null), "html", null, true);
            yield "
    </div>
  ";
        }
        // line 97
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["id", "display_id", "dom_id", "attributes", "title_prefix", "title", "title_suffix", "exposed", "header", "attachment_before", "rows", "empty", "pager", "attachment_after", "more", "footer", "feed_icons"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/claro/templates/media-library/views-view--media-library.html.twig";
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
        return array (  188 => 97,  182 => 94,  179 => 93,  176 => 92,  170 => 89,  167 => 88,  164 => 87,  158 => 85,  155 => 84,  149 => 81,  146 => 80,  143 => 79,  137 => 77,  135 => 76,  132 => 75,  126 => 72,  123 => 71,  121 => 70,  116 => 68,  113 => 67,  111 => 66,  108 => 65,  102 => 62,  99 => 61,  96 => 60,  90 => 57,  87 => 56,  84 => 55,  78 => 52,  75 => 51,  73 => 50,  68 => 49,  62 => 47,  60 => 46,  56 => 45,  51 => 44,  49 => 41,  48 => 40,  47 => 39,  46 => 38,  45 => 36,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/claro/templates/media-library/views-view--media-library.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\core\\themes\\claro\\templates\\media-library\\views-view--media-library.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 36, "if" => 46];
        static $filters = ["clean_class" => 38, "escape" => 44];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
                [0 => "clean_class", 1 => "escape"],
                [],
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
