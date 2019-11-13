<?php

namespace App\Twig;

use App\Translate\Translate;


class LocaleExtension extends \Twig\Extension\AbstractExtension
{

  private $translator;

  public function __construct(Translate $translate)
  {
    $this->translator = $translate::$translator;
  }

  public function getName()
  {
    return 'twig_translator';
  }

  public function getFunctions()
  {
    return array(
      new \Twig\TwigFunction('trans', array($this->translator, 'trans'))
    );
  }


}
