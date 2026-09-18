from fastapi import FastAPI, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, EmailStr
from typing import Optional
import sqlite3

app = FastAPI(title="Lead Capture API - GlobalScale", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

DB_NAME = "database.db"

def get_connection():
    conn = sqlite3.connect(DB_NAME)
    conn.row_factory = sqlite3.Row
    return conn

# Criar as tabelas automaticamente ao iniciar a API
def criar_tabelas():
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS leads (
            id_lead INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            telefone TEXT,
            pais TEXT NOT NULL,
            regiao TEXT NOT NULL,
            status TEXT DEFAULT 'NOVO',
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    """)
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS campanhas_utm (
            id_utm INTEGER PRIMARY KEY AUTOINCREMENT,
            id_lead INTEGER NOT NULL,
            origem TEXT NOT NULL,
            nome_campanha TEXT,
            FOREIGN KEY (id_lead) REFERENCES leads (id_lead)
        )
    """)
    conn.commit()
    conn.close()

criar_tabelas()

class UTMModel(BaseModel):
    origem: str = "direct"
    nome_campanha: Optional[str] = "organico"

class LeadCaptureModel(BaseModel):
    nome: str
    email: EmailStr
    telefone: Optional[str] = None
    pais: str
    regiao: str
    utm: UTMModel

@app.post("/api/v1/leads/capture", status_code=status.HTTP_201_CREATED)
def capture_lead(lead: LeadCaptureModel):
    try:
        conn = get_connection()
        cursor = conn.cursor()

        # Insere o Lead
        cursor.execute("""
            INSERT INTO leads (nome, email, telefone, pais, regiao, status)
            VALUES (?, ?, ?, ?, ?, 'NOVO')
        """, (lead.nome, lead.email, lead.telefone, lead.pais, lead.regiao.upper()))
        
        novo_id = cursor.lastrowid

        # Insere a UTM
        cursor.execute("""
            INSERT INTO campanhas_utm (id_lead, origem, nome_campanha)
            VALUES (?, ?, ?)
        """, (novo_id, lead.utm.origem, lead.utm.nome_campanha))

        conn.commit()
        conn.close()

        return {
            "status": "sucesso",
            "mensagem": "Lead cadastrado com sucesso no SQLite!",
            "id_lead": novo_id
        }
    except sqlite3.IntegrityError:
        raise HTTPException(status_code=400, detail="Este e-mail já está cadastrado.")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
@app.get("/api/v1/leads")
def listar_leads():
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        SELECT l.id_lead, l.nome, l.email, l.pais, l.regiao, l.status, c.origem, c.nome_campanha
        FROM leads l
        LEFT JOIN campanhas_utm c ON l.id_lead = c.id_lead
    """)
    dados = [dict(row) for row in cursor.fetchall()]
    conn.close()
    return dados