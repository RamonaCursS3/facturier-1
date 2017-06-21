<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\CsvFiles;
use AppBundle\Entity\Product;
use AppBundle\Entity\Category;
use AppBundle\Entity\Feature;
use AppBundle\Entity\FeatureName;
use AppBundle\Entity\UnitMeasure;
use AppBundle\Entity\ProductWarehouse;
use AppBundle\Entity\ProductLang;
use AppBundle\Entity\Warehouse;
use AppBundle\Form\CsvFilesType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;

/**
 * CsvFiles controller.
 *
 */
class CsvFilesController extends Controller
{
    /**
     * Lists all CsvFiles entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $csvFiles = $em->getRepository('AppBundle:CsvFiles')->findAll();

        return $this->render('csvfiles/index.html.twig', array(
            'csvFiles' => $csvFiles,
        ));
    }

    /**
     * Creates a new CsvFiles entity.
     *
     */
    public function newAction(Request $request)
    {

        $csvFile = new CsvFiles();
        $today = new \DateTime("now");   
        $csvFile->setDatCre($today);          
        $form = $this->createForm('AppBundle\Form\CsvFilesType', $csvFile);
        $form->add('submit', SubmitType::class, array(
            'label'=>'Create',
            'attr'=>array(
                'class'=>'btn btn-primary'
            ),
            'translation_domain'=>'AppBundle',
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($csvFile);
            $em->flush();
            return $this->redirectToRoute('csvfiles_index');
        }

        return $this->render('csvfiles/new.html.twig', array(
            'csvFile' => $csvFile,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a CsvFiles entity.
     *
     */
    public function showAction(CsvFiles $csvFile)
    {
        $deleteForm = $this->createDeleteForm($csvFile);

        return $this->render('csvfiles/show.html.twig', array(
            'csvFile' => $csvFile,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing CsvFiles entity.
     * @Template()
     */
    public function editAction(Request $request, CsvFiles $csvFile)
    {
        $deleteForm = $this->createDeleteForm($csvFile);
        $editForm = $this->createForm('AppBundle\Form\CsvFilesType', $csvFile);
        $editForm->add('submit', SubmitType::class, array(
            'label'=>'Edit',
            'attr'=>array(
                'class'=>'btn btn-success',
            ),
            'translation_domain'=>'AppBundle',
        ));

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $csvFile->upload();
            $em->persist($csvFile);
            $em->flush();

            return $this->redirectToRoute('csvfiles_show', array('id' => $csvFile->getId()));
        }

        return $this->render('csvfiles/edit.html.twig', array(
            'csvFile' => $csvFile,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a CsvFiles entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {

            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:CsvFiles')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CsvFiles entity.');
            }
            
            $em->remove($entity);
            $em->flush();

        return $this->redirect($this->generateUrl('csvfiles_index'));
    }

    /**
     * Creates a form to delete a CsvFiles entity.
     *
     * @param CsvFiles $csvFile The CsvFiles entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(CsvFiles $csvFile)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('csvfiles_delete', array('id' => $csvFile->getId())))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array(
                'label'=>'Delete',
                'attr'=>array(
                    'class'=>'btn btn-danger',
                ),
                'translation_domain'=>'AppBundle',
            ))
            ->getForm()
        ;
    }
    
    /**
     * Import CsvFiles entities from csv.
     *
     */
    public function importaAction(Request $request, $id, $tip)
    {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:CsvFiles')->find($id);
            
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CsvFiles entity.');
            }
            
            $em->detach($entity);
            
            $erori = call_user_func(array($this, $tip.'Action'), $request, $entity);
            
            $entity->setIsUsed(true);
            
            $em->merge($entity);
            
            $em->flush();
            $em->clear();
            
            if($erori){
                $this->addFlash(
                'notice',
                $erori.'. Sunt probleme cu importul.');

            }
            
            return $this->redirect($this->generateUrl('csvfiles_index'));
                
    }    
    
  /**
     * Process entities from csv.
     *
     */
    public function productCsvAction(Request $request, $csv)
    {
        $em = $this->getDoctrine()->getManager();        
        $my_file = $csv->getAbsolutePath(); 
        $myswitch = true;
        $wrong_csv_lines = '';
        $ktap_unique = array();
        $today = new \DateTime("now"); 
        
        if (($handle = fopen($my_file, "r")) !== FALSE) {
            $nr_crt = 1;
            while (($line = fgetcsv($handle,0,";")) !== FALSE) {   
                
                if(count($line)<=1) continue; //wrong sep => skip line
                
                // skip first line
                if ($myswitch) {
                    $myswitch=false;
                    continue;
                }
                
                $line = array_map('trim', $line);

                
                /*
                 * in cazul in care in fisierul csv sunt mai multe linii
                 * cu aceeasi reference, noi o folosim numai pe prima
                 * pentru asta folosim $ktap_unique
                 * 
                 */                

                    
                if(!in_array($line[0], $ktap_unique)){
                        
                    $ktap_unique[] = $line[0];
                                        
                    $product = $em->getRepository('AppBundle:Product')->findOneBy(array('reference'=>$line[0]));
                    
                    /*
                    * daca nu exista deja product adaugam unul nou
                    */

                    if(!$product){
                       
                        $product = new Product(); 
                        $product->setReference($line[0]);
                        $product->setDatCre($today);  
                          
                    }      
                    
                    // add product lang bg
                    $productLang = new ProductLang; 
                    $productLang->setProduct($product);
                    $productLang->setName($line[1]);
                    $productLang->setLang('bg');
                    $em->persist($productLang);
                    
                    // add product lang ro
                    $productLang = new ProductLang; 
                    $productLang->setProduct($product);
                    $productLang->setName($line[2]);
                    $productLang->setLang('ro');    
                    $em->persist($productLang);
                    
                    // add product lang en
                    $productLang = new ProductLang; 
                    $productLang->setProduct($product);
                    $productLang->setName($line[3]);
                    $productLang->setLang('en');   
                    $em->persist($productLang);
                    
                    
                    // add product features 
                    $feature_material = $em->getRepository('AppBundle:Feature')->findOneBy(array('bg'=>$line[4],'ro'=>$line[5],'en'=>$line[6]));    
                    
                    if($feature_material){
                            $product->addFeature($feature_material);
                    }  
                    
                    $feature_color = $em->getRepository('AppBundle:Feature')->findOneBy(array('bg'=>$line[7],'ro'=>$line[8],'en'=>$line[9]));
                    if($feature_color){
                            $product->addFeature($feature_color);
                    }  
                    
                    $feature_size = $em->getRepository('AppBundle:Feature')->findOneBy(array('bg'=>$line[10],'ro'=>$line[11],'en'=>$line[12]));
                    if($feature_size){
                            $product->addFeature($feature_size);
                    }   
                    
                    $category = $em->getRepository('AppBundle:Category')->find(1);
                    if($category){
                       $product->addCategory($category);
                    }
                    
                    $unitMeasure = $em->getRepository('AppBundle:UnitMeasure')->find(1);
                    if($unitMeasure){
                       $product->setUnitMeasure($unitMeasure);
                    }                    
                    
                    if(array_key_exists(13, $line))
                            $product->setEan($line[13]);
                    
                    if(array_key_exists(14, $line))
                        $product->setSalePrice($line[14]);
                   // $product->setManufacturer($line[15]);

                                   
                    $product->setDatUpd($today); 
                    $em->persist($product);
                    
                }
            }
            
            fclose($handle);
        } else {
            return 'Fisierul csv nu poate fi deschis!';          
        }
        
        $em->flush();
        $em->clear(); 
        
        return $wrong_csv_lines;
    }       

     /**
     * Process entities from csv.
     *
     */
    public function productStocCsvAction(Request $request, $csv)
    {
        $em = $this->getDoctrine()->getManager();
        
        $my_file = $csv->getAbsolutePath();
        
        $my_switch = true;
        $wrong_csv_lines = '';
        $today = new \DateTime("now"); 
        
        if (($handle = fopen($my_file, "r")) !== FALSE) {
            $nr_crt = 1;
            while (($line = fgetcsv($handle,0,";")) !== FALSE) {   
              // $line = explode(';', $line);
                
                $line = array_map('trim', $line);
                
                if(count($line) !== 2) {
                    
                    $erori = 'Separator gresit!';
                    continue;
                    
                } else {
                    
                    $insert=true;                    

                    $cod = trim($line[0]);
                    $cant = trim($line[1]);
                    $warehouseId = 2;
                    $ktnow = new \DateTime('now');  
                    
                    //look for product-warehouse
                    $productWarehouse=array();
                    $qb = $em->createQueryBuilder();
                    $qb -> select(array('iw','i','w'))                        
                                ->from('AppBundle:ProductWarehouse', 'iw')       
                                ->leftJoin('iw.product', 'i')
                                ->leftJoin('iw.warehouse', 'w')
                                ->andWhere($qb->expr()->eq('i.reference', '?1'))  
                                ->andWhere($qb->expr()->eq('w.id', '?2'))  
                                ->setParameter(1, $cod)
                                ->setParameter(2, $warehouseId);

                    $productWarehouse = $qb->getQuery()->getResult();
                    
                    if($productWarehouse){                        
                        foreach($productWarehouse as $entity){
                            $entity->setQuantity($cant);
                            $entity->setDatUpd($ktnow);
                            $insert=false;
                            break;
                        }
                    }
                    
                    $product = $em->getRepository('AppBundle:Product')->findOneBy(array('reference'=>$cod));
                    $warehouse = $em->getRepository('AppBundle:Warehouse')->find(2);
                    
                    // adaugam in ProductWarehouse
                    if($insert && $product && $warehouse){  
                        $productWarehouse = new ProductWarehouse();  
                        $productWarehouse->setProduct($product);                       
                        $productWarehouse->setWarehouse($warehouse);  
                        $productWarehouse->setQuantity($line[1]);  
                        $productWarehouse->setDatCre($ktnow); 
                        $productWarehouse->setDatUpd($ktnow); 
                        $em->persist($productWarehouse);  
                    }   
                    // pentru unicitate adaugam in $ktap_unique_smbid
                    $ktap_unique_smbid[] = $cod; 
                }
                
           
            }
            
            fclose($handle);
        } else {
            return 'Fisierul csv nu poate fi deschis!';          
        }
        
        $em->flush();
        $em->clear(); 
        
        return $wrong_csv_lines;
    }         
}