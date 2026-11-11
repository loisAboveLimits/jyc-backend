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

/* core/themes/claro/templates/system-themes-page.html.twig */
class __TwigTemplate_ab5f542bc8c23c444a829e11ac8ea255 extends Template
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
        yield "<div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
        yield ">
  ";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["theme_groups"] ?? null));
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
        foreach ($context['_seq'] as $context["_key"] => $context["theme_group"]) {
            // line 38
            yield "    ";
            // line 39
            $context["theme_group_classes"] = ["system-themes-list", ("system-themes-list--" . CoreExtension::getAttribute($this->env, $this->source,             // line 41
$context["theme_group"], "state", [], "any", false, false, true, 41)), "clearfix"];
            // line 45
            yield "
    ";
            // line 47
            $context["card_alignment"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["theme_group"], "state", [], "any", false, false, true, 47) == "installed")) ? ("horizontal") : ("vertical"));
            // line 49
            yield "
    ";
            // line 50
            if ( !(($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "first", [], "any", false, false, true, 50)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 51
                yield "      <hr>
    ";
            }
            // line 53
            yield "
    <div";
            // line 54
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["theme_group"], "attributes", [], "any", false, false, true, 54), "addClass", [($context["theme_group_classes"] ?? null)], "method", false, false, true, 54), "html", null, true);
            yield ">
      <h2 class=\"system-themes-list__header\">";
            // line 55
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme_group"], "title", [], "any", false, false, true, 55), "html", null, true);
            yield "</h2>
      <div class=\"card-list ";
            // line 56
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($context["card_alignment"] ?? null) == "horizontal")) ? ("card-list--two-cols") : ("card-list--four-cols")));
            yield "\">
        ";
            // line 57
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["theme_group"], "themes", [], "any", false, false, true, 57));
            foreach ($context['_seq'] as $context["_key"] => $context["theme"]) {
                // line 58
                yield "          ";
                // line 59
                $context["theme_classes"] = [(((($tmp = CoreExtension::getAttribute($this->env, $this->source,                 // line 60
$context["theme"], "is_default", [], "any", false, false, true, 60)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("theme-default") : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                 // line 61
$context["theme"], "is_admin", [], "any", false, false, true, 61)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("theme-admin") : ("")), "card", ("card--" .                 // line 63
($context["card_alignment"] ?? null)), "card-list__item"];
                // line 67
                yield "          ";
                // line 68
                $context["theme_title_classes"] = ["card__content-item", "heading-f"];
                // line 73
                yield "          ";
                // line 74
                $context["theme_description_classes"] = ["card__content-item"];
                // line 78
                yield "          <div";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "attributes", [], "any", false, false, true, 78), "addClass", [($context["theme_classes"] ?? null)], "method", false, false, true, 78), "setAttribute", ["aria-labelledby", CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "title_id", [], "any", false, false, true, 78)], "method", false, false, true, 78), "setAttribute", ["aria-describedby", (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description_id", [], "any", false, false, true, 78)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description_id", [], "any", false, false, true, 78)) : (null))], "method", false, false, true, 78), "html", null, true);
                yield ">
            ";
                // line 79
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "screenshot", [], "any", false, false, true, 79)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 80
                    yield "              <div class=\"card__image\">
                ";
                    // line 81
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "screenshot", [], "any", false, false, true, 81), "html", null, true);
                    yield "
              </div>
            ";
                }
                // line 84
                yield "            <div class=\"card__content-wrapper\">
              <div class=\"card__content\">
                <h3";
                // line 86
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $this->extensions['Drupal\Core\Template\TwigExtension']->createAttribute(["id" => CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "title_id", [], "any", false, false, true, 86)]), "addClass", [($context["theme_title_classes"] ?? null)], "method", false, false, true, 86), "html", null, true);
                yield " id=";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "title_id", [], "any", false, false, true, 86), "html", null, true);
                yield ">";
                // line 87
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "name", [], "any", false, false, true, 87), "html", null, true);
                yield " ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "version", [], "any", false, false, true, 87), "html", null, true);
                // line 88
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "notes", [], "any", false, false, true, 88)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 89
                    yield "                    (";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->safeJoin($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "notes", [], "any", false, false, true, 89), ", "));
                    yield ")";
                }
                // line 91
                yield "</h3>

                ";
                // line 93
                if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description", [], "any", false, false, true, 93)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description_id", [], "any", false, false, true, 93)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                    // line 94
                    yield "                  <div";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $this->extensions['Drupal\Core\Template\TwigExtension']->createAttribute(["id" => (((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description_id", [], "any", false, false, true, 94)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description_id", [], "any", false, false, true, 94)) : (null))]), "addClass", [($context["theme_description_classes"] ?? null)], "method", false, false, true, 94), "html", null, true);
                    yield ">
                    ";
                    // line 95
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "description", [], "any", false, false, true, 95), "html", null, true);
                    yield "
                  </div>";
                }
                // line 98
                yield "</div>

              <div class=\"card__footer\">
                ";
                // line 101
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "module_dependencies", [], "any", false, false, true, 101)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 102
                    yield "                  <div class=\"theme-info__requires\">
                    ";
                    // line 103
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Requires: @module_dependencies", ["@module_dependencies" => $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "module_dependencies", [], "any", false, false, true, 103))]));
                    yield "
                  </div>
                ";
                }
                // line 106
                yield "                ";
                // line 107
                yield "                ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "incompatible", [], "any", false, false, true, 107)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 108
                    yield "                  <small class=\"incompatible\">";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "incompatible", [], "any", false, false, true, 108), "html", null, true);
                    yield "</small>
                ";
                } else {
                    // line 110
                    yield "                    ";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["theme"], "operations", [], "any", false, false, true, 110), "html", null, true);
                    yield "
                ";
                }
                // line 112
                yield "              </div>
            </div>
          </div>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['theme'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 116
            yield "      </div>
    </div>
  ";
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
        unset($context['_seq'], $context['_key'], $context['theme_group'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 119
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "theme_groups", "loop"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/claro/templates/system-themes-page.html.twig";
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
        return array (  232 => 119,  215 => 116,  205 => 112,  199 => 110,  193 => 108,  190 => 107,  188 => 106,  182 => 103,  179 => 102,  177 => 101,  172 => 98,  167 => 95,  162 => 94,  160 => 93,  156 => 91,  151 => 89,  149 => 88,  145 => 87,  140 => 86,  136 => 84,  130 => 81,  127 => 80,  125 => 79,  120 => 78,  118 => 74,  116 => 73,  114 => 68,  112 => 67,  110 => 63,  109 => 61,  108 => 60,  107 => 59,  105 => 58,  101 => 57,  97 => 56,  93 => 55,  89 => 54,  86 => 53,  82 => 51,  80 => 50,  77 => 49,  75 => 47,  72 => 45,  70 => 41,  69 => 39,  67 => 38,  50 => 37,  45 => 36,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/claro/templates/system-themes-page.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\core\\themes\\claro\\templates\\system-themes-page.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["for" => 37, "set" => 39, "if" => 50];
        static $filters = ["escape" => 36, "safe_join" => 89, "t" => 103, "render" => 103];
        static $functions = ["create_attribute" => 86];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "for", 1 => "set", 2 => "if"],
                [0 => "escape", 1 => "safe_join", 2 => "t", 3 => "render"],
                [0 => "create_attribute"],
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
