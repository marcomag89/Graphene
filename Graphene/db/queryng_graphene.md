#Queryng Graphene
Una delle questioni ricorrenti è quella di recuperare i dati come meglio crediamo, l'approccio di graphene ingessa un po questo tipo di operazioni in favore dell' astrazione dello storage in quanto non tutte le basi di dati godono delle stesse peculiarità e caratteristiche
##Funzionamento
Quando bisogna reperire i dati, **Graphene** non tiene conto dell' obbligatorietà dei campi, e quindi della clausola `NOT_NULL`, pertanto è possibile popolare parzialmente un bean, per poi invocarne il metodo `.read()`, al fine di ottenere un bean dello stesso tipo ottenuto dallo storage.

##Selezione standard
Quando eseguiamo una `Bean.read()`  lo storage esegue una query per uguaglianze in AND presupponendo che il risultato sia unico. in caso di molteplici risultati seguirà un eccezione.
_es:_
Supponiamo di avere un bean del tipo
```
|-Person
.	|-name
.	|-surname
.	|-address
.	.	|-country
.	.	|-city
.	.	|-route
.	.	|-civicNo
```
popolandolo solo `surname` e `city` una persona che risiede in una certa città con un certo cognome.
```
|-Person
.	|-name
.	|-surname : Esposito
.	|-address
.	.	|-country
.	.	|-city : Napoli
.	.	|-route
.	.	|-civicNo
```
Di seguito un azione che esegue questa operazione:
```PHP
class ReadBySurnameAndCity extends Action{
	public function run(){
		$person = new Person();
		$person->setSurname('Esposito');
		$person->setAddress_city('Napoli');
		$result = $person.read();
		$this->sendBean($result);
	}
}
```
##Selezione multipla [in sviluppo]
in caso di selezione multipla, occorre utilizzare un altro costrutto di nome **BeanCollection** che consente allo storage di gestire le collezioni in maniera opportuna e rendere coscienti le azioni di voler effettivamente trattare collezioni di bean e non singoli bean. 
costruttore
```PHP
$collection = new BeanCollection($prototype)
```
dove `$prototype` rappresenta un bean parzialmente popolato.
**BeanCollection** mette a disposizione il metodo `read()` utilizzabile in maniera analoga al bean singolo.
Nel seguente esempio riprenderemo l'esempio di lettura del singolo cittadino con un determinato cognome e residente in una determinata città, estendendolo ad una collezione.
```PHP
class ReadBySurnameAndCityCollection extends Action{
	public function run(){
		$person = new Person();
		$person -> setSurname('Esposito');
		$person -> setAddress_city('Napoli');
		$personColl = new BeanCollection($person);
		$result = $personColl.read();
		$this->sendCollection($result);
	}
}
```
##Selezione avanzata
Molto spesso ci occorre selezionare elementi in base a operatori diversi da quelli di uguaglianza in and. Per questo **Graphene** integra nella lo strumento di selezione avanzata.
per eseguire una selezione avanzata occorre chiamare il metodo `read()` di un Bean o un Bean collection passandogli come argomento una **custom query**, ovvero una sintassi orientata alla query sviluppata da noi, per astrarre il driver dal livello azione.
esempio: 
```PHP
$bean->read($query)
```
 le politiche di selezione restano identiche a quelle presentate precedentemente.
