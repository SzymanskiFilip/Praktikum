<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\FormType\ContactType;
use App\Service\EmailService;
use App\Service\HashService;
use App\Service\QueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Some todo 's are here
 *
 * Class IndexController
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    /**
     * Path where to store the uploaded cv
     */
    public const CV_ASSET_DIR = '../uploads/assets';
    /**
     * Filename of the uploaded cv
     */
    public const CV_ASSET_FILENAME = 'Steckbrief.pdf';
    /**
     * The email address where the emails will send to from the contact form
     */
    protected $reciverEmailAddress;

    /**
     * EmailService used to send emails
     *
     * @var EmailService $emailService
     */
    protected EmailService $emailService;

    /**
     * HashService to generate and validate hashes
     * @var HashService $hashService
     */
    protected HashService $hashService;

    /**
     * IndexController constructor.
     * @param EmailService $emailService
     * @param HashService $hashService
     */
    public function __construct(EmailService $emailService, HashService $hashService, $reciverEmailAddress)
    {
        /*
         * Weise den Variablen in dieser Klasse die Objekte zu, welche in den Parametern mitgegeben werden.
         */
        $this->emailService = $emailService;
        $this->hashService = $hashService;
        $this->reciverEmailAddress = $reciverEmailAddress;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, QueryService $queryService, Filesystem $filesystem): Response
    {
        $showDownloadButton = false;
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // ## Start des If-Blocks
            try {
                $this->emailService->sendMail(
                    "email@email.com",
                    $this->reciverEmailAddress,
                    $contact->getSubject(),
                    'emails/contact.html.twig',
                    [
                        'name' => $contact->getName(),
                        'email' => $contact->getEmail(),
                        'message' => $contact->getMessage()
                    ]
                );
                $this->addFlash('contact-success', 'Ihre Nachricht wurde erfolgreich gesendet!');
            } catch (\Exception $e) {
                $this->addFlash('contact-danger', 'Ihre Nachricht kann derzeit nicht zugestellt werden!');

                // render template
                return $this->renderForm('landingpage/index.html.twig', [
                    'showDownloadButton' => $showDownloadButton,
                    'form' => $form,
                ]);
            }

            // redirect to home route
            return $this->redirectToRoute('home');
        // ## Ende des If-Blocks
        }

        $showDownloadButton = $this->hashService->validateHash($request);
        return $this->renderForm('landingpage/index.html.twig', [
                'form' => $form, 
                'showDownloadButton' => $showDownloadButton, 
                'hash' => $this->hashService->getHash($request)
        ]);
    }

    /**
     * @Route("/downloadCv", name="downloadCv")
     */
    public function getUploadedCv(Filesystem $filesystem, Request $request): Response
    {

        if ($this->hashService->validateHash($request)) {
            $filename = self::CV_ASSET_DIR . '/' . self::CV_ASSET_FILENAME;
            if(!$filesystem->exists($filename)){
                return new Response("File doesn't exist", 401);
            } else {
                return new BinaryFileResponse($filename, 200);
            }
        } else {
            return $this->redirectToRoute('home');
        }
    }
}
