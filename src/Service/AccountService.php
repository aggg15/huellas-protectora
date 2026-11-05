<?php
declare(strict_types=1);
namespace App\Service;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountService
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) {}
    public function register(array $data): User
    {
        $data=array_map(static fn($v)=>is_string($v)?$v:'',$data);
        $email=mb_strtolower(trim($data['email']??''));$name=trim($data['name']??'');$password=$data['password']??'';$errors=[];
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>180) { $errors['email']='Indica un email válido.'; }
        if ($name==='' || mb_strlen($name)>100) { $errors['name']='Indica tu nombre (máximo 100 caracteres).'; }
        if (mb_strlen($password)<12 || strlen($password)>200) { $errors['password']='Usa una contraseña de entre 12 y 200 caracteres.'; }
        if ($password!==($data['confirmation']??'')) { $errors['confirmation']='Las contraseñas no coinciden.'; }
        if (!$errors && $this->em->getRepository(User::class)->findOneBy(['email'=>$email])) { $errors['email']='Ya hay una cuenta con este email. Accede con ella.'; }
        if ($errors) { throw new ValidationError($errors); }
        $user=new User($email,$name);$user->password=$this->hasher->hashPassword($user,$password);
        try { $this->em->persist($user);$this->em->flush(); }
        catch (UniqueConstraintViolationException) { throw new ValidationError(['email'=>'Ya hay una cuenta con este email.']); }
        return $user;
    }
}
