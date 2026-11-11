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

/* modules/contrib/webform/templates/webform-handler-email-summary.html.twig */
class __TwigTemplate_7e5a210f85f6869eaf92257f6a35fde4 extends Template
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
        yield "
";
        // line 14
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "debug", [], "any", false, false, true, 14)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<strong class=\"color-error\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Debugging is enabled"));
            yield "</strong><br />";
        }
        // line 15
        yield "<b>";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("To:"));
        yield "</b> ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::replace(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "to_mail", [], "any", false, false, true, 15), ["," => ", "]), "html", null, true);
        yield "<br />
";
        // line 16
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "cc_mail", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<b>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("CC:"));
            yield "</b> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::replace(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "cc_mail", [], "any", false, false, true, 16), ["," => ", "]), "html", null, true);
            yield "<br />";
        }
        // line 17
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "bcc_mail", [], "any", false, false, true, 17)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<b>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("BCC:"));
            yield "</b> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::replace(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "bcc_mail", [], "any", false, false, true, 17), ["," => ", "]), "html", null, true);
            yield "<br />";
        }
        // line 18
        yield "<b>";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("From:"));
        yield "</b> ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "from_name", [], "any", false, false, true, 18)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "from_name", [], "any", false, false, true, 18), "html", null, true);
        }
        yield " &lt;";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "from_mail", [], "any", false, false, true, 18), "html", null, true);
        yield "&gt;<br />
";
        // line 19
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "reply_to", [], "any", false, false, true, 19)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<b>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Reply to:"));
            yield "</b> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "reply_to", [], "any", false, false, true, 19), "html", null, true);
            yield "<br />";
        }
        // line 20
        yield "<b>";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Subject:"));
        yield "</b> ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "subject", [], "any", false, false, true, 20), "html", null, true);
        yield "<br />
<b>";
        // line 21
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Settings:"));
        yield "</b> ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "html", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("HTML") : (t("Plain text"))));
        yield " ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "html", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "attachments", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) ? ("/") : ("")));
        yield " ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "attachments", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (t("Attachments")) : ("")));
        yield " ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "twig", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (t("(Twig)")) : ("")));
        yield "<br />
<b>";
        // line 22
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Sent when:"));
        yield "</b> ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "states", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join(CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "states", [], "any", false, false, true, 22), "; "), "html", null, true);
        } else {
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Custom"));
        }
        yield "<br />
";
        // line 23
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "theme_name", [], "any", false, false, true, 23)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<b>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Theme:"));
            yield "</b> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "theme_name", [], "any", false, false, true, 23), "html", null, true);
            yield "<br />";
        }
        // line 24
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "langcode", [], "any", false, false, true, 24)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<b>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Language:"));
            yield "</b> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["settings"] ?? null), "langcode", [], "any", false, false, true, 24), "html", null, true);
            yield "<br />";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["settings"]);        return; yield;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/webform/templates/webform-handler-email-summary.html.twig";
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
        return array (  133 => 24,  125 => 23,  115 => 22,  103 => 21,  96 => 20,  88 => 19,  77 => 18,  69 => 17,  61 => 16,  54 => 15,  48 => 14,  45 => 13,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/webform/templates/webform-handler-email-summary.html.twig", "C:\\xampp\\htdocs\\jyc\\new\\backend\\modules\\contrib\\webform\\templates\\webform-handler-email-summary.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 14];
        static $filters = ["t" => 14, "escape" => 15, "replace" => 15, "join" => 22];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if"],
                [0 => "t", 1 => "escape", 2 => "replace", 3 => "join"],
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
