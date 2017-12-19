<?php

namespace CWP\Cwp\Extension;

use SilverStripe\Core\Extension,
    SilverStripe\Forms\Form;

/**
 * Customises the comment form to conform to government usability standards
 *
 * {@see CommentingController}
 */
class CwpCommentingExtension extends Extension
{

    /**
     *
     * @param \CWP\Cwp\Extension\Form $form
     * @retunr void
     */
    public function alterCommentForm(Form $form)
    {
        $fields = $form->Fields();


        if ($emailField = $fields->dataFieldByName('Email')) {
            $emailField
                    ->setTitle(_t('CwpCommentingExtension.EMAIL_TITLE', 'Email'))
                    ->setDescription(_t('CwpCommentingExtension.WILL_NOT_BE_PUBLISHED', 'Will not be published.'));
        }

        if ($urlField = $fields->dataFieldByName('URL')) {
            $urlField
                    ->setTitle(_t('CwpCommentingExtension.WEBSITE_TITLE', 'Your website (optional)'))
                    ->setAttribute('placeholder', 'http://');
        }
    }

}
