import { datosPagomovil } from './pagomovil.js'
import { detalles } from './detalles.js'

var datosCliente, transaccion;

var campos = ['referencia', 'telefono', 'banco', 'codigo', 'cedula', 'fecha', 'monto']

export function reportarPagomovil(datosCliente, transaccion1) {
    transaccion = transaccion1
    let principal = document.getElementById('principal')
    principal.innerHTML = ''
    let textCard = 'REPORTAR PAGO MOVIL'
    let textButton1 = 'Continuar'
    let textButton2 = 'Volver'

    principal.classList.add('principalSett')

    let card1 = document.createElement('div')
    card1.classList.add('card', 'cardSett')

    let cardheader = document.createElement('div')
    cardheader.classList.add('card-header')
    cardheader.innerHTML = textCard
    let bodyC = document.createElement('div')
    bodyC.classList.add('card-body')

    for (let index = 0; index < campos.length; index++) {
        if(index != 3){
            if(index == 2){
                bodyC.appendChild(cargarBanco())
            }else{
              if(index !== 1 || transaccion.modopago !== 'transferencia'){
                let rowB1 = document.createElement('div')
                rowB1.classList.add('row', 'my-2')
                let colRowB1 = document.createElement('div')
                let colRowB2 = document.createElement('div')
                colRowB1.classList.add('col-md-6')
                colRowB2.classList.add('col-md-6')

                //creamos los input
                let label1 = document.createElement('label')
                label1.for = `${campos[index]}`

                if(index==4){
                  label1.innerHTML = `RIF/CEDULA:`
                }else{
                  label1.innerHTML = `${campos[index].toUpperCase()}:`
                }
                  
                colRowB1.appendChild(label1)

                let valor1 = document.createElement('input')
                if(campos[index]=='fecha'){
                  valor1.type = 'date'
                  valor1.required
                }
                valor1.autocomplete = "off"
                valor1.classList.add(`${campos[index]}`, 'form-control')
                valor1.value = ''
                valor1.id = `${campos[index]}`
                colRowB2.appendChild(valor1)

                rowB1.appendChild(colRowB1)
                rowB1.appendChild(colRowB2)

                bodyC.appendChild(rowB1)
              }    
            }
        }
    }

    let button1 = document.createElement('button')
    let button2 = document.createElement('button')
    button1.classList.add('buttonR')
    button2.classList.add('buttonR')
    button1.innerHTML = textButton1
    button2.innerHTML = textButton2
    
    let rowFooter = document.createElement('div')
    rowFooter.classList.add('row')
    
    let colRow1 = document.createElement('div')
    let colRow2 = document.createElement('div')

    colRow1.classList.add('col-md-6')
    colRow2.classList.add('col-md-6')

    rowFooter.appendChild(colRow1)
    rowFooter.appendChild(colRow2)

    colRow1.appendChild(button1)
    colRow2.appendChild(button2)

    let footer = document.createElement('div')
    footer.classList.add('card-footer')
    footer.appendChild(rowFooter)


    card1.appendChild(cardheader)
    card1.appendChild(bodyC)
    card1.appendChild(footer)

    principal.appendChild(card1)

    
    button1.addEventListener("click", (event) => {
      if(validarcion()){
        transaccion.referencia = document.getElementById('referencia').value
        if(transaccion.modopago !== 'transferencia')
          transaccion.telefono = document.getElementById('telefono').value
        transaccion.cedula = document.getElementById('cedula').value
        transaccion.fecha = document.getElementById('fecha').value
        transaccion.monto = document.getElementById('monto').value
        detalles(datosCliente, transaccion)
      }
        
    });

    button2.addEventListener("click", (event) => {   
        datosPagomovil(datosCliente, transaccion)
    });

    document.getElementById('banco').addEventListener('change', selectBanco)

    function selectBanco(e) {
        let index = e.target.selectedIndex;
        transaccion.codigo = e.target.value
        transaccion.banco = e.target.options[index].text
    }


}

const validarcion = () => {

  
  if (!validar('#referencia'))
    return false
  if(transaccion.modopago !== 'transferencia')
    if (!validar('#telefono'))
        return false
    
      
  if(document.querySelector('#banco').value == '0'){
    document.querySelector('#banco').focus()
    return false
  }

  
  if (!validar('#cedula'))
    return false

  if (!validar('#fecha'))
    return false

  if (!validar('#monto'))
    return false

  return true
          
}

const validar = ( selector, num = 4 ) => {
  
  // Se verifica si selector es una cadena:
  if ( typeof selector !== "string" )
    return false;
  
  // Se obtiene el elemento mediante el selector:
  let text = document.querySelector( selector );
  
  // Se verifica si el elemento obtenido es nulo.
  // Si es nulo devolverá «false»:
  if ( text === null )
    return false;
  
  // Se eliminan los caracteres y se determina
  // que la cantidad mínima sea «num». Es decir,
  // que la caja de texto no se encuentre vacía y 
  // la cantidad mínima de caracteres sea «num»:
  if ( (text.value.trim().length < num) ){
    text.focus();
    return false;
  }
  
  return true;
};

const validarFecha = () => {

}


function funcionBancos(){
    let bancos
    return bancos =
    {
        "bancos": [
          {
            "name": "BANCO DE VENEZUELA",
            "codigo": "0102"
          },
          {
            "name": "100% BANCO",
            "codigo": "0156"
          },
          {
            "name": "BANCAMIGA BANCO MICROFINANCIERO CA",
            "codigo": "0172"
          },
          {
            "name": "BANCARIBE",
            "codigo": "0114"
          },
          {
            "name": "BANCO ACTIVO",
            "codigo": "0171"
          },
          {
            "name": "BANCO AGRICOLA DE VENEZUELA",
            "codigo": "0166"
          },
          {
            "name": "BANCO BICENTENARIO DEL PUEBLO",
            "codigo": "0175"
          },
          {
            "name": "BANCO CARONI",
            "codigo": "0128"
          },
          {
            "name": "BANCO DEL TESORO",
            "codigo": "0163"
          },
          {
            "name": "BANCO EXTERIOR",
            "codigo": "0115"
          },
          {
            "name": "BANCO FONDO COMUN",
            "codigo": "0151"
          },
          {
            "name": "BANCO INTERNACIONAL DE DESARROLLO",
            "codigo": "0173"
          },
          {
            "name": "BANCO MERCANTIL",
            "codigo": "0105"
          },
          {
            "name": "BANCO NACIONAL DE CREDITO",
            "codigo": "0191"
          },
          {
            "name": "BANCO PLAZA",
            "codigo": "0138"
          },
          {
            "name": "BANCO SOFITASA",
            "codigo": "0137"
          },
          {
            "name": "BANCO VENEZOLANO DE CREDITO",
            "codigo": "0104"
          },
          {
            "name": "BANCRECER",
            "codigo": "0168"
          },
          {
            "name": "BANESCO",
            "codigo": "0134"
          },
          {
            "name": "BANFANB",
            "codigo": "0177"
          },
          {
            "name": "BANGENTE",
            "codigo": "0146"
          },
          {
            "name": "BANPLUS",
            "codigo": "0174"
          },
          {
            "name": "BBVA PROVINCIAL",
            "codigo": "0108"
          },
          {
            "name": "DELSUR BANCO UNIVERSAL",
            "codigo": "0157"
          },
          {
            "name": "MI BANCO",
            "codigo": "0169"
          },
          {
            "name": "N58 BANCO DIGITAL BANCO MICROFINANCIERO S A",
            "codigo": "0178"
          }
        ]
    }
    
}

function cargarBanco(){
    var bancos = funcionBancos()

    let bancosS = document.createElement('select')
    bancosS.id = 'banco'
    bancosS.classList.add('form-select')
    
    bancosS.innerHTML += "<option value='0'>SELECCIONE..</option>"; 

    bancos.bancos.forEach( e => {
        bancosS.innerHTML += "<option value='"+e['codigo']+"'>"+e['name']+"</option>"; 
    })

    //bancosS.value = transaccion['codigo']
    
    let rowBB0 = document.createElement('div')
    rowBB0.classList.add('row', 'my-2')
    let colRowBB01 = document.createElement('div')
    let colRowBB02 = document.createElement('div')
    colRowBB01.classList.add('col-md-6')
    colRowBB02.classList.add('col-md-6')
    let labelB0 = document.createElement('label')
    labelB0.innerHTML = `BANCO:`
    colRowBB01.appendChild(labelB0)
    colRowBB02.appendChild(bancosS)
    rowBB0.appendChild(colRowBB01)
    rowBB0.appendChild(colRowBB02)
    return rowBB0
}