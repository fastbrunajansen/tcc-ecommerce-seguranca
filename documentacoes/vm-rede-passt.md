# Rede da VM (passt) — configuração e isolamento

Registro de 26/10/2026. Esta configuração fica **fora do repositório** (na definição da VM no libvirt do Manjaro), por isso está documentada aqui.

## Como a VM se conecta

A VM `ubuntu25.10` (Ubuntu Server 26.04.1 LTS, criada pelo GNOME Boxes) usa rede do tipo `user` com o backend **passt**. Nesse modo, a VM **não tem IP próprio na rede Wi-Fi**: o único caminho de entrada são as portas que o `passt` abre no computador hospedeiro (Manjaro) e encaminha para a VM.

| Porta no Manjaro | Destino na VM | Uso |
|---|---|---|
| 8080 | 8080 | Loja (contêiner WordPress) |
| 2222 | 22 | SSH |

O endereço `192.168.15.150` é o IP do **Manjaro** na interface Wi-Fi (`wlp43s0`), não da VM.

## O problema encontrado

Sem o atributo `address`, o `passt` escutava em **todas as interfaces** do Manjaro (`ss` mostrava `*:8080`). Assim, qualquer aparelho conectado ao mesmo Wi-Fi alcançava a loja (inclusive a página vulnerável do Cenário A) em `http://192.168.15.150:8080` e o SSH da VM em `192.168.15.150:2222`.

## A correção (26/10/2026)

No Manjaro:

```bash
virsh --connect qemu:///session dumpxml --inactive ubuntu25.10 > ~/vm-ubuntu.xml
cp ~/vm-ubuntu.xml ~/vm-ubuntu-backup.xml
sed -i "s/<portForward proto='tcp'>/<portForward proto='tcp' address='127.0.0.1'>/g" ~/vm-ubuntu.xml
virsh --connect qemu:///session define ~/vm-ubuntu.xml
virsh --connect qemu:///session shutdown ubuntu25.10
virsh --connect qemu:///session start ubuntu25.10
```

Trecho resultante da configuração:

```xml
<interface type='user'>
  <portForward proto='tcp' address='127.0.0.1'>
    <range start='8080' to='8080'/>
  </portForward>
  <portForward proto='tcp' address='127.0.0.1'>
    <range start='2222' to='22'/>
  </portForward>
  <backend type='passt'/>
</interface>
```

Verificação no Manjaro (`ss -ltnp | grep -E "8080|2222"`): `127.0.0.1:8080` e `127.0.0.1:2222`.

Dentro da VM, o contêiner continua publicado em `8080:80` (todas as interfaces da VM), porque a VM só é alcançável pelo `passt`.

## Como acessar desde então

- SSH: `ssh -p 2222 bruna@localhost`
- Loja: `http://localhost:8080` (navegador do Manjaro, sem túnel)

Para desfazer: `virsh --connect qemu:///session define ~/vm-ubuntu-backup.xml` e reiniciar a VM.
