#include <SPI.h>
#include <UIPEthernet.h>
#include <avr/wdt.h>

// Configurações do Servidor
const char SERVER_IP[] = "206.42.28.182";
const uint16_t SERVER_PORT = 8089;

// Limites de tamanho das strings (conforme servidor)
const uint8_t MAX_LOCAL_LENGTH = 36;
const uint8_t MAX_STRING_LENGTH = 100;

// Configurações do dispositivo
const char mac_address[] = "02:53:45:4b:fd:2a";
const char local[] = "-5.87586,-35.19745";
const char municipio[] = "Natal";
const char tipo[] = "poço";
const char deviceName[] = "PC-Oficina";

// Configuração dos pinos
const uint8_t flowSensorPin = 2;
const uint8_t ledPin = 7;
const uint8_t errorLedPin = 6;
const uint8_t connectedLedPin = 5;

// Variáveis para cálculo de fluxo
volatile uint32_t pulseCount = 0;
const float calibrationFactor = 0.475;
float flowRate = 0.0;
float volume = 0.0;
float totalVolume = 0.0;

// Controle de tempo
unsigned long lastFlowCalc = 0;
unsigned long lastServerSend = 0;
const unsigned long FLOW_CALC_INTERVAL = 1000;  // 1 segundo
const unsigned long SERVER_SEND_INTERVAL = 10000;  // 10 segundos

// Buffer circular para flowRate
float flowRates[100];
uint8_t index = 0;
float maxFlowRate = 0.0;
float lastMaxFlowRate = 0.0;

// Função de interrupção para contagem de pulsos
void pulseCounter() {
  pulseCount++;
}

void setup() {
  // Configuração dos pinos
  pinMode(10, OUTPUT);
  pinMode(flowSensorPin, INPUT_PULLUP);
  pinMode(ledPin, OUTPUT);
  pinMode(errorLedPin, OUTPUT);
  pinMode(connectedLedPin, OUTPUT);
  
  // Configura interrupção para contagem de pulsos
  attachInterrupt(digitalPinToInterrupt(flowSensorPin), pulseCounter, RISING);
  
  // Configura DHCP com retry
  uint8_t macaddr[] = { 0x02, 0x53, 0x45, 0x4B, 0xFD, 0x2A };
  uint8_t dhcpTries = 0;
  digitalWrite(connectedLedPin, LOW);
  digitalWrite(errorLedPin, HIGH);
  
  while (dhcpTries < 3) {
    if (Ethernet.begin(macaddr) == 1) {
      digitalWrite(connectedLedPin, HIGH);
      digitalWrite(errorLedPin, LOW);
      break;
    }
    dhcpTries++;
    delay(1000);
  }
  
  // Configura Watchdog
  wdt_enable(WDTO_8S);
}

void loop() {
  unsigned long currentMillis = millis();
  
  // Cálculo de fluxo e volume a cada segundo
  if (currentMillis - lastFlowCalc >= FLOW_CALC_INTERVAL) {
    noInterrupts(); // Desabilita interrupções durante cálculos críticos
    uint32_t pulseCountCopy = pulseCount;
    pulseCount = 0;
    interrupts(); // Reabilita interrupções
    
    lastFlowCalc = currentMillis;
    
    // Calcula fluxo
    if (pulseCountCopy > 0) {
      flowRate = (pulseCountCopy / calibrationFactor);
      flowRates[index] = flowRate;
      index = (index + 1) % 50;
      
      if (flowRate > maxFlowRate) {
        maxFlowRate = flowRate;
      }
    } else {
      flowRate = 0.0;
    }
    
    // Atualiza volume
    volume = flowRate / 60.0;
    totalVolume += volume;
    
    // Indica atividade via LED
    digitalWrite(ledPin, HIGH);
    delayMicroseconds(50000); // 50ms em microssegundos para maior precisão
    digitalWrite(ledPin, LOW);
  }
  
  // Envio para servidor a cada 10 segundos
  if (currentMillis - lastServerSend >= SERVER_SEND_INTERVAL) {
    lastServerSend = currentMillis;
    
    float flowRateToSend;
    if (flowRate == 0.0) {
      if (maxFlowRate > 0.0) {
        flowRateToSend = maxFlowRate;
        lastMaxFlowRate = maxFlowRate;
        maxFlowRate = 0.0;
      } else {
        flowRateToSend = lastMaxFlowRate;
      }
    } else {
      flowRateToSend = flowRate;
    }
    
    if (sendToServer(flowRateToSend)) {
      totalVolume = 0.0; // Zera volume apenas após envio bem-sucedido
    }
  }
  
  wdt_reset();
}

bool sendToServer(float flowRateToSend) {
  uint8_t retries = 3;
  bool success = false;
  
  while (retries > 0 && !success) {
    EthernetClient client;
    
    if (client.connect(SERVER_IP, SERVER_PORT)) {
      digitalWrite(connectedLedPin, HIGH);
      digitalWrite(errorLedPin, LOW);
      
      // Prepara strings para JSON
      char flowRateStr[10];
      char totalVolumeStr[10];
      dtostrf(flowRateToSend, 6, 2, flowRateStr);
      dtostrf(totalVolume, 6, 2, totalVolumeStr);
      
      // Monta o JSON
      char jsonBuffer[256];
      snprintf(jsonBuffer, sizeof(jsonBuffer),
        "{\"mac_address\":\"%s\","
        "\"local\":\"%s\","
        "\"municipio\":\"%s\","
        "\"tipo\":\"%s\","
        "\"nome\":\"%s\","
        "\"fluxo\":%s,"
        "\"volume\":%s}",
        mac_address, local, municipio, tipo, deviceName,
        flowRateStr, totalVolumeStr);
      
      // Envia cabeçalho HTTP
      client.println(F("POST / HTTP/1.1"));
      client.print(F("Host: "));
      client.println(SERVER_IP);
      client.println(F("Content-Type: application/json"));
      client.print(F("Content-Length: "));
      client.println(strlen(jsonBuffer));
      client.println(F("Connection: close"));
      client.println();
      
      // Envia o JSON
      client.println(jsonBuffer);
      
      // Aguarda brevemente pela confirmação do envio
      uint8_t timeout = 100; // 100ms de timeout
      while (!client.available() && timeout > 0) {
        delay(1);
        timeout--;
      }
      
      // Fecha conexão
      client.stop();
      success = true;
    } else {
      retries--;
      if (retries > 0) {
        delay(1000); // Espera 1 segundo antes de tentar novamente
      }
    }
  }
  
  if (!success) {
    digitalWrite(connectedLedPin, LOW);
    digitalWrite(errorLedPin, HIGH);
  }
  
  return success;
}