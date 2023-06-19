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

        // load form input from request if they are available
        /*
         * Damit das Formular überprüfen kann, ob es schon einmal ausgefüllt wurde, übergeben wir dem Formular den
         * aktuellen Request, indem alle notwendigen Informationen stehen.
         *
         * Benutze dazu die Funktion 'handleRequest' des Formulars
         */
        // check if form is submitted and if all values are syntactically correct
        /*
         * Nur wenn das Formular schon einmal ausgefüllt wurde und die eingegebenen Daten valide sind, sollte eine
         * Email versendet werden.
         *
         * Überprüfe nun mithilfe des Formulars und den Funktionen 'isSubmitted' und 'isValid' innerhalb eines
         * if-Statements ob dieser Zustand zutrifft.
         *
         * Dabei markieren die Kommentare
         * '## Start des If-Blocks' und '## Ende des If-Blocks'
         * den Bereich welcher innerhalb des if-blockes steht.
         */
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

        

        /*
         * Überprüfe mithilfe des 'hashService' und der Funktion 'validateHash', ob der Hash welcher möglicherweise
         * in der URL steht valide ist.
         * Das Ergebnis, welches ein boolischer Wert ist, wird der Variable 'showDownloadButton' zugewiesen.
         */
        $showDownloadButton = $this->hashService->validateHash($request);
        

        // render template
        /*
         * Als Letztes müssen, wir noch das Formular rendern also dass es generiert wird. Dies wird mit der
         * Funktion 'renderForm', welche ebenfalls in dieser Klasse ist, gemacht. Die Funktion benötigt zwei Parameter:
         * - Der Name des Templates, welches zum rendern verwendet werden soll: landingpage/index.html.twig
         * - Der Context in Form eines Arrays, welcher die Daten zum rendern enthält
         *
         * Der Context muss folgende Daten enthalten:
         * - das 'form' welches wir oben erstellt haben
         * - die Variable 'showDownloadButton'
         * - und der Hash welchen du mithilfe folgendem Aufruf bekommst:
         *      - "$this->hashService->getHash($request)"
         *
         * Den Rückgabewert der Funktion werden wir dieses mal nicht in eine Variable speichern, sondern werden ihn
         * direkt mit dem Schlüsselwort 'return' zurückgeben.
         *
         */
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
        /*
         * Überprüfe mithilfe des HashServices, ob der im Request übergebene Hash valide ist.
         *
         * Wenn nicht, dann returne einen Response mit der Meldung, dass man nicht autorisiert ist: 'Not authorized'.
         * Füge den passenden HTTP Response Code an.
         */
        /*
        if($this->hashService->validateHash($request)){

        } else {
            
            $res = new Response('Error hash not valid' . $this->hashService->getHash($request) , 401);
            return $res;
        }*/
        

        /*
         * Zuerst bauen wir den Dateinamen des Dokumentes zusammen. Dazu gibt es in dieser Klasse eine Konstante, die
         * 'CV_ASSET_DIR' heißt. In dieser Konstante ist der Pfad zum Ordner, indem die Datei liegt gespeichert. Als
         * Nächstes benötigen wir noch die Konstante 'DIRECTORY_SEPARATOR'. Diese enthält je nach Betriebssystem das
         * Zeichen, mit welchem ein Pfad getrennt ist. Zuletzt befindet sich in dieser Klasse noch eine weitere
         * Konstante. Die Konstante mit dem Namen: 'CV_ASSET_FILENAME'.
         *
         * Verbinde diese drei Konstanten miteinander und speichere sie in eine Variable ('filename')
         */
        $filename = self::CV_ASSET_DIR . '/' . self::CV_ASSET_FILENAME;
        /*
         * Überprüfe mithilfe des Filesystem Service, ob eine Datei mit dem eben erstellen Dateinamen existiert. Ist
         * das nicht der Fall, gebe erneut einen Response zurück der besagt, dass noch keine Datei gesetzt wurde und dem
         * Statuscode 404.
         */
        if(!$filesystem->exists($filename)){
            return new Response("File doesn't exist", 404);
        } else {
            return new BinaryFileResponse($filename, 200);
        }

        /*
         * Wenn der Aufruf an diese Stelle gelangt ist, kann die Datei zurückgegeben werden. Dazu erstellen wir einen
         * neuen BinaryFileResponse und übergeben ihm im Konstruktor einfach den Dateinamen.
         *
         * Das neu erstellte Objekt returnen wir.
         */
    }
}
