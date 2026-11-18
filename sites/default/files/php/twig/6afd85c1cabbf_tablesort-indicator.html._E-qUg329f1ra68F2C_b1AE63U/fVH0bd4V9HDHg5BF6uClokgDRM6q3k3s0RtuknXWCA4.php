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

/* core/themes/claro/templates/admin/tablesort-indicator.html.twig */
class __TwigTemplate_9cad1550c3a85ba663e46537e8ce099a extends Template
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
        // line 13
        $context["classes"] = ["tablesort", ("tablesort--" .         // line 15
($context["style"] ?? null))];
        // line 18
        yield "<span";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 18), "html", null, true);
        yield ">
  ";
        // line 19
        if (CoreExtension::inFilter(($context["style"] ?? null), ["asc", "desc"])) {
            // line 20
            yield "    <span class=\"visually-hidden\">
      ";
            // line 21
            if ((($context["style"] ?? null) == "asc")) {
                // line 22
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Sort ascending"));
                yield "
      ";
            } else {
                // line 24
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Sort descending"));
                yield "
      ";
            }
            // line 26
            yield "    </span>
  ";
        }
        // line 28
        yield "</span>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["style", "attributes"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/claro/templates/admin/tablesort-indicator.html.twig";
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
        return array (  74 => 28,  70 => 26,  65 => 24,  60 => 22,  58 => 21,  55 => 20,  53 => 19,  48 => 18,  46 => 15,  45 => 13,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/claro/templates/admin/tablesort-indicator.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\core\\themes\\claro\\templates\\admin\\tablesort-indicator.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 13, "if" => 19];
        static $filters = ["escape" => 18, "t" => 22];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
                [0 => "escape", 1 => "t"],
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
