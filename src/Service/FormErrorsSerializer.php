<?php


namespace App\Service;


use Symfony\Component\Form\FormInterface;

class FormErrorsSerializer
{
    /**
     * Iterate through all forms and fetch errors into single array.
     *
     * @param FormInterface $form
     * @return array
     */
    public function getErrors(FormInterface $form): array
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrors($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}