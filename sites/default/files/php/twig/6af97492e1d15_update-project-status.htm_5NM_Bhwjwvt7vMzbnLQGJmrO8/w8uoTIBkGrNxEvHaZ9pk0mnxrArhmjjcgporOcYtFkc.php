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

/* core/modules/update/templates/update-project-status.html.twig */
class __TwigTemplate_37373a8fdfce96caa07bec3cdf35e876 extends Template
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
        // line 31
        $context["status_classes"] = [(((CoreExtension::getAttribute($this->env, $this->source,         // line 32
($context["project"] ?? null), "status", [], "any", false, false, true, 32) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::NOT_SECURE"))) ? ("project-update__status--security-error") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 33
($context["project"] ?? null), "status", [], "any", false, false, true, 33) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::REVOKED"))) ? ("project-update__status--revoked") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 34
($context["project"] ?? null), "status", [], "any", false, false, true, 34) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::NOT_SUPPORTED"))) ? ("project-update__status--not-supported") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 35
($context["project"] ?? null), "status", [], "any", false, false, true, 35) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::NOT_CURRENT"))) ? ("project-update__status--not-current") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 36
($context["project"] ?? null), "status", [], "any", false, false, true, 36) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::CURRENT"))) ? ("project-update__status--current") : (""))];
        // line 39
        yield "<div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["status"] ?? null), "attributes", [], "any", false, false, true, 39), "addClass", ["project-update__status", ($context["status_classes"] ?? null)], "method", false, false, true, 39), "html", null, true);
        yield ">";
        // line 40
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["status"] ?? null), "label", [], "any", false, false, true, 40)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 41
            yield "<span>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["status"] ?? null), "label", [], "any", false, false, true, 41), "html", null, true);
            yield "</span>";
        } else {
            // line 43
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["status"] ?? null), "reason", [], "any", false, false, true, 43), "html", null, true);
        }
        // line 45
        yield "  <span class=\"project-update__status-icon\">
    ";
        // line 46
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["status"] ?? null), "icon", [], "any", false, false, true, 46), "html", null, true);
        yield "
  </span>
</div>

<div class=\"project-update__title\">";
        // line 51
        if ((($tmp = ($context["url"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 52
            yield "<a href=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
            yield "\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
            yield "</a>";
        } else {
            // line 54
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
        }
        // line 56
        yield "  ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["existing_version"] ?? null), "html", null, true);
        yield "
  ";
        // line 57
        if (((($context["install_type"] ?? null) == "dev") && (($tmp = ($context["datestamp"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 58
            yield "    <span class=\"project-update__version-date\">(";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["datestamp"] ?? null), "html", null, true);
            yield ")</span>
  ";
        }
        // line 60
        yield "</div>

";
        // line 62
        if ((($tmp = ($context["versions"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 63
            yield "  ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["versions"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["version"]) {
                // line 64
                yield "    ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["version"], "html", null, true);
                yield "
  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['version'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
        }
        // line 67
        yield "
";
        // line 69
        $context["extra_classes"] = [(((CoreExtension::getAttribute($this->env, $this->source,         // line 70
($context["project"] ?? null), "status", [], "any", false, false, true, 70) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::NOT_SECURE"))) ? ("project-not-secure") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 71
($context["project"] ?? null), "status", [], "any", false, false, true, 71) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::REVOKED"))) ? ("project-revoked") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 72
($context["project"] ?? null), "status", [], "any", false, false, true, 72) == Twig\Extension\CoreExtension::constant("Drupal\\update\\UpdateManagerInterface::NOT_SUPPORTED"))) ? ("project-not-supported") : (""))];
        // line 75
        yield "<div class=\"project-updates__details\">
  ";
        // line 76
        if ((($tmp = ($context["extras"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 77
            yield "    <div class=\"extra\">
      ";
            // line 78
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["extras"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["extra"]) {
                // line 79
                yield "        <div";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["extra"], "attributes", [], "any", false, false, true, 79), "addClass", [($context["extra_classes"] ?? null)], "method", false, false, true, 79), "html", null, true);
                yield ">
          ";
                // line 80
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["extra"], "label", [], "any", false, false, true, 80), "html", null, true);
                yield ": ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["extra"], "data", [], "any", false, false, true, 80), "html", null, true);
                yield "
        </div>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['extra'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 83
            yield "    </div>
  ";
        }
        // line 85
        yield "  ";
        $context["includes"] = Twig\Extension\CoreExtension::join(($context["includes"] ?? null), ", ");
        // line 86
        yield "  ";
        if ((($tmp = ($context["disabled"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 87
            yield "    ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Includes:"));
            yield "
    <ul>
      <li>
        ";
            // line 90
            yield t("Enabled: %includes", ["%includes" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(            // line 91
($context["includes"] ?? null)), ]);
            // line 93
            yield "      </li>
      <li>
        ";
            // line 95
            $context["disabled"] = Twig\Extension\CoreExtension::join(($context["disabled"] ?? null), ", ");
            // line 96
            yield "        ";
            yield t("Disabled: %disabled", ["%disabled" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(            // line 97
($context["disabled"] ?? null)), ]);
            // line 99
            yield "      </li>
    </ul>
  ";
        } else {
            // line 102
            yield "    ";
            yield t("Includes: %includes", ["%includes" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(            // line 103
($context["includes"] ?? null)), ]);
            // line 105
            yield "  ";
        }
        // line 106
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["project", "status", "url", "title", "existing_version", "install_type", "datestamp", "versions", "extras", "disabled"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/modules/update/templates/update-project-status.html.twig";
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
        return array (  202 => 106,  199 => 105,  197 => 103,  195 => 102,  190 => 99,  188 => 97,  186 => 96,  184 => 95,  180 => 93,  178 => 91,  177 => 90,  170 => 87,  167 => 86,  164 => 85,  160 => 83,  148 => 80,  143 => 79,  139 => 78,  136 => 77,  134 => 76,  131 => 75,  129 => 72,  128 => 71,  127 => 70,  126 => 69,  123 => 67,  112 => 64,  107 => 63,  105 => 62,  101 => 60,  95 => 58,  93 => 57,  88 => 56,  85 => 54,  78 => 52,  76 => 51,  69 => 46,  66 => 45,  63 => 43,  58 => 41,  56 => 40,  52 => 39,  50 => 36,  49 => 35,  48 => 34,  47 => 33,  46 => 32,  45 => 31,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/modules/update/templates/update-project-status.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\core\\modules\\update\\templates\\update-project-status.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 31, "if" => 40, "for" => 63, "trans" => 90];
        static $filters = ["escape" => 39, "join" => 85, "t" => 87, "placeholder" => 91];
        static $functions = ["constant" => 32];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if", 2 => "for", 3 => "trans"],
                [0 => "escape", 1 => "join", 2 => "t", 3 => "placeholder"],
                [0 => "constant"],
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
