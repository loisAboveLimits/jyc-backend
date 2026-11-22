<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\jyc_api\BlogImporter;
use Drupal\jyc_api\CourseImporter;
use Drupal\jyc_api\FaqImporter;
use Drupal\jyc_api\ImportArchive;
use Drupal\jyc_api\TeamImporter;
use Drupal\jyc_api\TestimonyImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Content > Import content: upload a tar.gz / tgz / tar.bz2 archive. What it
 * imports depends on the file inside:
 *   - posts.json (+ images/): Blogs posts, see BlogImporter;
 *   - faqs.json: FAQ items, see FaqImporter;
 *   - teams.json (+ images/): Teams members, see TeamImporter;
 *   - testimonies.json: Testimonies, see TestimonyImporter;
 *   - courses.json (+ images/): Courses, see CourseImporter.
 */
final class BlogImportForm extends FormBase {

  private const EXTENSIONS = ['tar.gz', 'tgz', 'tar.bz2'];

  /** Manifest file => [content type, label] */
  private const KINDS = [
    'posts.json' => ['blogs', 'Blog posts'],
    'faqs.json' => ['faq', 'FAQs'],
    'teams.json' => ['teams', 'Team members'],
    'testimonies.json' => ['testimonies', 'Testimonies'],
    'courses.json' => ['courses', 'Courses'],
  ];

  public function __construct(
    private readonly BlogImporter $blogImporter,
    private readonly FaqImporter $faqImporter,
    private readonly TeamImporter $teamImporter,
    private readonly TestimonyImporter $testimonyImporter,
    private readonly CourseImporter $courseImporter,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      BlogImporter::create($container),
      FaqImporter::create($container),
      TeamImporter::create($container),
      TestimonyImporter::create($container),
      CourseImporter::create($container),
    );
  }

  public function getFormId(): string {
    return 'jyc_api_blog_import';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Imports content, with its Arabic translations, from an archive containing one of:'),
    ];
    $form['kinds'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('<code>posts.json</code> and an <code>images</code> folder: <strong>Blogs</strong> posts. A post whose URL (<code>/blogs/…</code>) already exists is updated.'),
        $this->t('<code>faqs.json</code>: <strong>FAQ</strong> items with their category and order. An FAQ with the same category and question is updated.'),
        $this->t('<code>teams.json</code> and an <code>images</code> folder: <strong>Teams</strong> members with their position and photo. A member with the same name is updated.'),
        $this->t('<code>testimonies.json</code>: <strong>Testimonies</strong> (name, headline, review and rating). An item with the same name and headline is updated.'),
        $this->t('<code>courses.json</code> and an <code>images</code> folder: <strong>Courses</strong> with their photo and booking link. A course with the same title is updated.'),
      ],
    ];
    $form['archive'] = [
      '#type' => 'file',
      '#title' => $this->t('Archive'),
      '#description' => $this->t('Allowed types: @extensions.', ['@extensions' => implode(' ', self::EXTENSIONS)]),
      '#attributes' => ['accept' => '.tar.gz,.tgz,.tar.bz2,.gz,.bz2'],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $upload = $this->getRequest()->files->get('files', [])['archive'] ?? NULL;
    if (!$upload || !$upload->isValid()) {
      $form_state->setErrorByName('archive', $this->t('Choose an archive to upload (it may also be larger than the server allows: @max).', [
        '@max' => ini_get('upload_max_filesize'),
      ]));
      return;
    }
    $name = strtolower($upload->getClientOriginalName());
    $allowed = array_filter(self::EXTENSIONS, fn(string $extension) => str_ends_with($name, ".$extension"));
    if (!$allowed) {
      $form_state->setErrorByName('archive', $this->t('Only @extensions files can be imported.', ['@extensions' => implode(', ', self::EXTENSIONS)]));
      return;
    }
    $form_state->set('archive_path', $upload->getRealPath());
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $path = $form_state->get('archive_path');
    if (!$path) {
      return;
    }
    try {
      $archive = ImportArchive::open($path);
      $found = $archive->manifest(...array_keys(self::KINDS));
      if ($found === NULL) {
        throw new \RuntimeException('The archive contains no posts.json, faqs.json, teams.json, testimonies.json or courses.json file.');
      }
      [$manifestName, $manifest] = $found;
      $result = match ($manifestName) {
        'faqs.json' => $this->faqImporter->importFrom($archive, $manifest),
        'teams.json' => $this->teamImporter->importFrom($archive, $manifest),
        'testimonies.json' => $this->testimonyImporter->importFrom($archive, $manifest),
        'courses.json' => $this->courseImporter->importFrom($archive, $manifest),
        default => $this->blogImporter->importFrom($archive, $manifest),
      };
    }
    catch (\RuntimeException $e) {
      $this->messenger()->addError($e->getMessage());
      return;
    }
    finally {
      @unlink($path);
    }

    [$type, $label] = self::KINDS[$manifestName];
    $message = isset($result['images'])
      ? '@label imported: @created created, @updated updated, @images new images.'
      : '@label imported: @created created, @updated updated.';
    $this->messenger()->addStatus($this->t($message, [
      '@label' => $label,
      '@created' => $result['created'],
      '@updated' => $result['updated'],
      '@images' => $result['images'] ?? 0,
    ]));
    foreach ($result['errors'] as $error) {
      $this->messenger()->addWarning($this->t('Not imported: @error', ['@error' => $error]));
    }
    if ($result['created'] + $result['updated'] > 0) {
      $form_state->setRedirectUrl(Url::fromRoute('system.admin_content', [], ['query' => ['type' => $type]]));
    }
  }

}
