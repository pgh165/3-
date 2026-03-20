# 온도 센서 모니터링 프로젝트 프로세스

## 프로젝트 개요

0~100 사이의 가상 온도 데이터를 생성하여 MySQL에 저장하고, PHP 기반 동적 웹 페이지에서 실시간 모니터링하는 시스템

## 시스템 구성

| 구성 요소 | 기술 | 설명 |
|-----------|------|------|
| 데이터 생성기 | Python | 3초 간격으로 난수 온도 생성 |
| 데이터베이스 | MySQL (tempdb) | temp 테이블에 온도 데이터 저장 |
| API 서버 | PHP (api.php) | DB에서 최신 30건 JSON 반환 |
| 모니터링 UI | HTML/JS (index.php) | 차트 + 테이블 실시간 표시 |
| 웹 서버 | Apache2 | PHP 실행 및 정적 파일 서빙 |

## 데이터 흐름

1. `generate_temp.py`가 0~100 사이의 난수를 생성
2. 생성된 온도 데이터를 MySQL `tempdb.temp` 테이블에 INSERT
3. 브라우저가 5초마다 `api.php`에 AJAX 요청
4. `api.php`가 MySQL에서 최신 30건을 조회하여 JSON 반환
5. `index.php`의 JavaScript가 JSON 데이터를 파싱
6. Chart.js 차트 및 HTML 테이블을 동적으로 업데이트

## 전체 시스템 블록도

```mermaid
flowchart LR
    subgraph DataGen["🐍 데이터 생성"]
        A["generate_temp.py<br/>Python"]
        R["random(0~100)<br/>난수 생성"]
    end

    subgraph DB["🗄️ 데이터베이스"]
        M["MySQL<br/>tempdb.temp"]
    end

    subgraph Server["🌐 웹 서버 (Apache2)"]
        API["api.php<br/>JSON API"]
        WEB["index.php<br/>모니터링 UI"]
    end

    subgraph Client["🖥️ 브라우저"]
        JS["JavaScript<br/>fetch (5초 간격)"]
        CHART["Chart.js<br/>온도 차트"]
        TABLE["HTML Table<br/>데이터 목록"]
    end

    R -->|"온도값"| A
    A -->|"INSERT<br/>3초 간격"| M
    M -->|"SELECT<br/>최신 30건"| API
    API -->|"JSON"| JS
    JS --> CHART
    JS --> TABLE
    WEB -.->|"페이지 로드"| JS
```

## 상세 데이터 처리 흐름

```mermaid
sequenceDiagram
    participant PY as generate_temp.py
    participant DB as MySQL (tempdb)
    participant API as api.php
    participant BR as 브라우저

    loop 3초마다
        PY->>PY: random(0, 100) 난수 생성
        PY->>DB: INSERT INTO temp (temperature)
        DB-->>PY: OK
    end

    loop 5초마다
        BR->>API: GET /temp/api.php
        API->>DB: SELECT ... ORDER BY created_at DESC LIMIT 30
        DB-->>API: 결과 반환
        API-->>BR: JSON 응답
        BR->>BR: 차트 업데이트
        BR->>BR: 테이블 업데이트
        BR->>BR: 통계 업데이트 (현재/평균/최고/최저)
    end
```

## 파일 구조

```mermaid
graph TD
    ROOT["📁 ~/Desktop/temp"]
    ROOT --> PY["📄 generate_temp.py<br/>데이터 생성 스크립트"]
    ROOT --> API["📄 api.php<br/>REST API"]
    ROOT --> IDX["📄 index.php<br/>모니터링 대시보드"]
    ROOT --> PRJ["📄 project.md<br/>프로젝트 개요"]
    ROOT --> PRC["📄 process.md<br/>프로세스 문서"]

    LINK["🔗 /var/www/html/temp"]
    LINK -.->|"symlink"| ROOT

    style PY fill:#3b82f6,color:#fff
    style API fill:#a855f7,color:#fff
    style IDX fill:#f97316,color:#fff
    style PRJ fill:#64748b,color:#fff
    style PRC fill:#64748b,color:#fff
    style LINK fill:#22c55e,color:#fff
```

## DB 스키마

```mermaid
erDiagram
    temp {
        INT id PK "AUTO_INCREMENT"
        FLOAT temperature "0~100 난수"
        DATETIME created_at "DEFAULT CURRENT_TIMESTAMP"
    }
```
