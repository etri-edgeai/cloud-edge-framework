-----------------------------------------------------
# 연구노트 
 - 기술문서명 : RaspberryPI 4b, 8GB에 k8s cluster 설치하기
 - 과제명 : 능동적 즉시 대응 및 빠른 학습이 가능한 적응형 경량 엣지 연동분석 기술개발
 - 영문과제명 : Development of Adaptive and Lightweight Edge-Collaborative Analysis Technology for Enabling Proactively Immediate Response and Rapid Learning
 - Acknowledgement : This work was supported by Institute of Information & communications Technology Planning & Evaluation (IITP) grant funded by the Korea government(MSIT) (No. 2021-0-00907, Development of Adaptive and Lightweight Edge-Collaborative Analysis Technology for Enabling Proactively Immediate Response and Rapid Learning).
 - 작성자 : 박종빈
 
 - 날짜 : 2023-06-12
 - 연구자 : 박종빈
-----------------------------------------------------

## 개요

- 컨네이너화된 워크로드와 서비스를 관리하기 위한 도구로 k8s(kubernetes)가 존재합니다.
- 본 연구에서는 라즈베리파이 초경량 에지 디바이스를 이용하여 k8s 클러스터를 구축하는 방법을 정리합니다.
- 설치 절차는 향후 스크립트를 실행하여 자동 처리하는 것이 바람직합니다.


## 참고 문헌

- 본 연구 및 문서 작성은 아래의 링크들을 참고하여 진행했습니다.
- https://jussiroine.com/2021/06/building-a-kubernetes-cluster-using-raspberry-pi-4/
- https://www.youngju.dev/blog/202212/raspberrypi_kubernetes_install



## 목표

- 6대의 라즈베리파이(Raspberry pi, rpi)가 있습니다.
- 1번 rpi 는 master node로 동작하고, 나머지 2 ~ 6번 rpi는 worker node로 동작합니다.



## 설치 순서

### 1. 그룹 설정

- Ubuntu 64bit OS가 설치된 라즈베리파이 6대를 준비합니다(수량은 변경 및 확장될 수 있습니다).
- 준비된 라즈베리파이는 동일 네트워크에 존재함을 가정합니다.
- 각각 라즈베리파이 다바이스에서 /boot/cmdline.txt 파일을 열어 첫줄 맨 뒤에, "cgroup_memory=1 cgroup_enable=memory" 내용을 추가후 reboot합니다.


- 즉, 아래와 같이 파일을 변경합니다.

```bash

$ sudo vi /boot/cmdline.txt

  cgroup_memory=1 cgroup_enable=memory

```

- 변경된 '/boot/cmdline.txt' 파일은 아래와 같습니다.

```bash
console=serial0,115200 console=tty1 root=PARTUUID=c8d8ee5a-02 rootfstype=ext4 fsck.repair=yes rootwait quiet splash plymouth.ignore-serial-consoles cgroup_memory=1 cgroup_enable=memory
```

- 리부팅하여 설정을 적용합니다.

```bash

$ sudo reboot

```

- 앞서의 과정을 수행하지 않으면 Master node 및 Worker node에서 프로그램 설치시 오류가 발생할 수 있습니다.



### 2. Master node 설정


- 설치 스크립트를 수행합니다.

```bash

$ curl -sfL https://get.k3s.io | sh -

```

- 마스터 노드의 토큰을 읽은 후 이를 소정의 장소에 보관합니다.

```bash

$ sudo cat  /var/lib/rancher/k3s/server/node-token

```

- 예를들어 아래와 같은 토큰이 출력되었습니다.

```bash
K102ae5a6cfc0634f30e6d6a7d0ef5cd9c19f098dd795fe164ff5878ee71b4b8c63::server:a4947c7a5e2cf637fcebdc73ffacffdf
```


### 3. Worker node 설정


- Worker 노드로 기능할 rpi들 {2, 3, 4, 5, 6}에 접속하여 아래와 같은 명령어를 실행합니다.
- 이때 master node에서 얻은 토큰이 필요합니다.

```bash

$ curl -sfL https://get.k3s.io | K3S_URL=https://192.168.1.241:6443 K3S_TOKEN=K102ae5a6cfc0634f30e6d6a7d0ef5cd9c19f098dd795fe164ff5878ee71b4b8c63::server:a4947c7a5e2cf637fcebdc73ffacffdf sh -

```


- 기본적으로 쿠버네티스 API 서버는 TLS에 의해 보호되는 첫번째 non-localhost 네트워크 인터페이스의 6443번 포트에서 수신을 대기합니다 (출처: https://kubernetes.io/ko/docs/concepts/security/controlling-access/)
- 일반적인 쿠버네티스 클러스터에서 API는 443번 포트에서 서비스합니다 (출처: https://kubernetes.io/ko/docs/concepts/security/controlling-access/)
- 단, 포트번호는 --secure-port 플래그를 통해, 수신 대기 IP 주소는 --bind-address 플래그를 통해 변경될 수 있습니다 (출처: https://kubernetes.io/ko/docs/concepts/security/controlling-access/)


- 그리고 K3S_URL에는 https 프로토콜을 지원하므로 주소에 https를 추가해줘야 합니다.


### 4. 정상적으로 설치되었는지 확인합니다.

- Master node로 접속합니다.
- 아래와 같은 명령어를 실행합니다.

```bash

$ sudo kubectl get nodes

```

- 설치가 문제없다면 아래와 유사한 결과를 얻게 됩니다.

```bash
$ sudo kubectl get nodes
NAME      STATUS   ROLES                  AGE     VERSION
rpi6402   Ready    <none>                 3m39s   v1.26.5+k3s1
rpi6403   Ready    <none>                 2m26s   v1.26.5+k3s1
rpi6404   Ready    <none>                 2m20s   v1.26.5+k3s1
rpi6405   Ready    <none>                 2m13s   v1.26.5+k3s1
rpi6406   Ready    <none>                 2m8s    v1.26.5+k3s1
rpi6401   Ready    control-plane,master   17m     v1.26.5+k3s1
```

### 5. k8s dashboard를 설치합니다.

- 참고문헌은 다음과 같습니다 (https://docs.k3s.io/installation/kube-dashboard)

- Master node로 접속합니다.

- (Master node) 아래와 같은 명령어를 실행합니다.

```bash
GITHUB_URL=https://github.com/kubernetes/dashboard/releases
VERSION_KUBE_DASHBOARD=$(curl -w '%{url_effective}' -I -L -s -S ${GITHUB_URL}/latest -o /dev/null | sed -e 's|.*/||')
sudo k3s kubectl create -f https://raw.githubusercontent.com/kubernetes/dashboard/${VERSION_KUBE_DASHBOARD}/aio/deploy/recommended.yaml

```

- (Master node) 홈 디렉토리에 dashboard.admin-user.yml 파일을 생성하고 다음의 내용을 기입합니다.

```bash
$ vi dashboard.admin-user.yml


apiVersion: v1
kind: ServiceAccount
metadata:
  name: admin-user
  namespace: kubernetes-dashboard

```

- (Master node) 홈 디렉토리에 dashboard.admin-user-role.yml 파일을 생성하고 다음의 내용을 기입합니다.

```bash
$ vi dashboard.admin-user-role.yml


apiVersion: rbac.authorization.k8s.io/v1
kind: ClusterRoleBinding
metadata:
  name: admin-user
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
- kind: ServiceAccount
  name: admin-user
  namespace: kubernetes-dashboard
  
```
 
- (Master node) deploy admin-user configuration

```bash
sudo k3s kubectl create -f dashboard.admin-user.yml -f dashboard.admin-user-role.yml

```


- (Master node) get bearer token

```bash
sudo k3s kubectl -n kubernetes-dashboard create token admin-user

eyJhbGciOiJSUzI1NiIsImtpZCI6ImNiZ1BpU0NwVnFhUUdnTE9uOHE2V084UFRFbmFfbEp2a0Z3Q0kwVkJjVFkifQ.eyJhdWQiOlsiaHR0cHM6Ly9rdWJlcm5ldGVzLmRlZmF1bHQuc3ZjLmNsdXN0ZXIubG9jYWwiLCJrM3MiXSwiZXhwIjoxNjg2NTQ3MjcxLCJpYXQiOjE2ODY1NDM2NzEsImlzcyI6Imh0dHBzOi8va3ViZXJuZXRlcy5kZWZhdWx0LnN2Yy5jbHVzdGVyLmxvY2FsIiwia3ViZXJuZXRlcy5pbyI6eyJuYW1lc3BhY2UiOiJrdWJlcm5ldGVzLWRhc2hib2FyZCIsInNlcnZpY2VhY2NvdW50Ijp7Im5hbWUiOiJhZG1pbi11c2VyIiwidWlkIjoiMGU1ZTU1MDctMDZmNi00NGIyLTk1N2ItODMxYjBlMGU3YmExIn19LCJuYmYiOjE2ODY1NDM2NzEsInN1YiI6InN5c3RlbTpzZXJ2aWNlYWNjb3VudDprdWJlcm5ldGVzLWRhc2hib2FyZDphZG1pbi11c2VyIn0.HsJ_goL35YRG35vgOGNyyYlv8X2y_NND5fJSU1ty5zm7fLz8dOnyD-KG70eaMC4BY0DxeucnE87RkNkNsUJR1aWkxODkRN8WRcgwBnEGey8IrI86-YBNcTS0Swfs80ELT5PMJ7OuZ12PsUp7XQhWln6OwzJ-aHztTGSi1rLeXIJAABGyVOO6E2EMW0Q4YQG3jdKTkBG8eImkbhnTIEzUAbPwtL-jg6YdBQCY6nP7Vuh40dJ6X70Qil0HPWXXO-btSJYR3_lHn7GMHGU9eSu5FCeYTR4qQHwwHxD0FCWGouKoF4GfQrrYLV3E_1BulOj_qI_kN8BYNEGJ20G-zaBABA

```


- (Master node) start dashboard

```bash
sudo k3s kubectl proxy --port=9090 --address=0.0.0.0 --accept-hosts='^*$'
Starting to serve on [::]:9090
```


- (네트워크 내부의 다른 컴퓨터) 대시보드 접속 테스트

```bash
http://192.168.1.241:9090/api/v1/namespaces/kubernetes-dashboard/services/https:kubernetes-dashboard:/proxy/ 에 접속.
```

- 보안 문제로 로그인이 안되고, https 프로토콜을 사용하라는 오류가 발생합니다.




- 상기 대시보드 로그인 문제를 해결하기 위해 https를 설정합니다.
- 참고문헌 : https://yunhochung.medium.com/k8s-%EB%8C%80%EC%89%AC%EB%B3%B4%EB%93%9C-%EC%84%A4%EC%B9%98-%EB%B0%8F-%EC%99%B8%EB%B6%80-%EC%A0%91%EC%86%8D-%EA%B8%B0%EB%8A%A5-%EC%B6%94%EA%B0%80%ED%95%98%EA%B8%B0-22ed1cd0999f


- 인증서를 위한 Key파일, CSR(Certificate Signing Request, 인증서 서명 요청 파일) 생성


```bash

$ mkdir certs; cd certs

$ openssl genrsa -des3 -passout pass:x -out dashboard.pass.key 2048

    Generating RSA private key, 2048 bit long modulus (2 primes)
    ..........................................................+++++
    ............................................+++++
    e is 65537 (0x010001)


$ openssl rsa -passin pass:x -in dashboard.pass.key -out dashboard.key

    writing RSA key

$ openssl req -new -key dashboard.key -out dashboard.csr


```


- SSL 생성

```bash

$ openssl x509 -req -sha256 -days 365 -in dashboard.csr -signkey dashboard.key -out dashboard.crt

```

- k8s secret 생성


```bash

$ cd ..
$ sudo kubectl create secret generic kubernetes-dashboard-certs --from-file=./certs -n kube-system


    secret/kubernetes-dashboard-certs created
```

- kubectl 적용하기

```bash
$ wget https://raw.githubusercontent.com/kubernetes/dashboard/v1.10.1/src/deploy/recommended/kubernetes-dashboard.yaml
# dashboard server 수정. spec type을 LoadBalancer로 지정.
$ sudo kubectl apply -f kubernetes-dashboard.yaml
```



## 연구 내용

- Port 방화벽 처리 (8000번 포트를 여는 예시)

```bash
$ sudo iptables -I INPUT -p tcp --dport 8000 -j ACCEPT
$ sudo iptables -I OUTPUT -p tcp --sport 8000 -j ACCEPT
```